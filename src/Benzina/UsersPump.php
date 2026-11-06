<?php

namespace App\Benzina;

use App\Entity\Territory;
use App\Entity\User\Organization;
use App\Entity\User\Person;
use App\Entity\User\User;
use App\Entity\User\UserType;
use App\Library\Link;
use App\Service\Project\TerritoryService;
use App\Service\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Goteo\Benzina\Pump\ArrayPumpTrait;
use Goteo\Benzina\Pump\PumpInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class UsersPump implements PumpInterface
{
    use ArrayPumpTrait;
    use DoctrineLoggablePumpTrait;
    use UsersPumpTrait;
    use TerritoryPumpTrait;
    use DatabasePumpTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private TerritoryService $territoryService,
    ) {
        $this->setFlushBatchSize(8);
    }

    public function supports(mixed $sample): bool
    {
        if ($this->hasAllKeys($sample, self::USER_KEYS)) {
            return true;
        }

        return false;
    }

    public function pump(mixed $record, array $context): void
    {
        $user = new User();
        $user = $this->processUser($user, $record, $context);

        try {
            $this->persist($user, $context);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $em = $this->managerRegistry->resetManager();
            $this->setEntityManager($em);

            $usersRepo = $em->getRepository(User::class);
            $user = $usersRepo->findOneBy(['email' => $record['email']]);

            if ($user) {
                $user->setDeduped(true);
                $user->addDedupedId($record['id']);

                $this->persist($user, $context);

                return;
            }

            $user = new User();
            $user = $this->processUser($user, $record, $context);
            $user->setHandle(UserService::asHandle($record['id'], 16, 255));

            $this->persist($user, $context);

            return;
        }
    }

    private function processUser(User $user, array $record, array $context): User
    {
        $user->setHandle($this->buildHandle($record));
        $user->setPassword($record['password'] ?? '');
        $user->setEmail($record['email']);
        $user->setEmailConfirmed(false);
        $user->setActive(false);
        $user->setMigrated(true);
        $user->setMigratedId($record['id']);
        $user->setDateCreated($this->getDateCreated($record));
        $user->setDateUpdated(new \DateTime());
        $user->setType($this->getUserType($record));
        $user->setLinks($this->getLinks($record));
        $user->setTerritory($this->getTerritory($record));
        $user->setDescription($record['about']);
        $user->setRoles($this->getRoles($record, $context));
        $user->setAvatar($this->getAvatar($record));

        match ($user->getType()) {
            UserType::Individual => $user = $this->setUserPerson($record, $user),
            UserType::Organization => $user = $this->setUserOrganization($record, $user),
        };

        return $user;
    }

    private function buildHandle(array $record): string
    {
        try {
            $handle = UserService::asHandle($record['id'], 8, 255);
        } catch (\Exception $e) {
            $handle = UserService::asHandle($record['email'], 8, 255);
        }

        return $handle;
    }

    private function getDateCreated(array $record): \DateTime
    {
        $created = new \DateTime($record['created'] ?? '0000-00-00');

        if ($created > new \DateTime('2011-01-01')) {
            return $created;
        }

        return new \DateTime($record['modified'] ?? 'now');
    }

    private function getUserType(array $record): UserType
    {
        switch ($record['legal_entity']) {
            case 0:
            case 1:
                return UserType::Individual;
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                return UserType::Organization;
            default:
                return UserType::Individual;
        }
    }

    private function setUserPerson(array $record, User $user): User
    {
        [$firstName, $lastName] = UserService::guessNames($record['name']);

        $person = new Person();
        $person->setFirstName($firstName);
        $person->setLastName($lastName);

        $user->setPerson($person);

        return $user;
    }

    private function setUserOrganization(array $record, User $user): User
    {
        $org = new Organization();
        $org->setBusinessName($record['name']);

        $user->setOrganization($org);

        return $user;
    }

    private function getLinks(array $record): array
    {
        $linkableKeys = [
            'twitter',
            'facebook',
            'instagram',
            'identica',
            'linkedin',
        ];

        $links = [];
        foreach ($linkableKeys as $key) {
            $url = $record[$key];

            if ($url === null || $url === '') {
                continue;
            }

            $isValidUrl = Validation::createIsValidCallable(constraints: new Url());
            if (!$isValidUrl($url)) {
                continue;
            }

            $link = new Link();
            $link->url = $url;
            $link->rel = 'external';

            $links[] = $link;
        }

        return $links;
    }

    private function getTerritory(array $record): Territory
    {
        if ($record['location'] === null) {
            return Territory::unknown();
        }

        $cleanAddress = $this->cleanLocation($record['location'], 2);

        if ($cleanAddress === '') {
            return Territory::unknown($record['location']);
        }

        return $this->territoryService->search($cleanAddress);
    }

    private function getRoles(array $record, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `user_role` r WHERE r.user_id = :user'
        );

        $query->execute(['user' => $record['id']]);

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (!$results || empty($results)) {
            return [];
        }

        $roles = [];
        foreach ($results as $result) {
            if (in_array($result['role_id'], ['superadmin', 'manager'])) {
                $roles[] = 'ROLE_ADMIN';
            }
        }

        return $roles;
    }

    private function getAvatar(array $record): ?string
    {
        $image = $record['avatar'];

        if ($image === null || $image === '') {
            return null;
        }

        if (!\str_contains($image, '.')) {
            return null;
        }

        return \sprintf('https://s3.eu-west-1.amazonaws.com/goteoassets.org/images/%s', $image);
    }
}
