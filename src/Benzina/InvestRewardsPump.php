<?php

namespace App\Benzina;

use App\Entity\Address;
use App\Entity\Gateway\Charge;
use App\Entity\Project\Reward;
use App\Entity\Project\RewardClaim;
use App\Entity\Project\RewardClaimStatus;
use App\Entity\User\User;
use App\Gateway\ChargeStatus;
use App\Repository\Gateway\ChargeRepository;
use App\Repository\Project\RewardRepository;
use App\Service\UserService;
use Goteo\Benzina\Pump\ArrayPumpTrait;
use Goteo\Benzina\Pump\PumpInterface;

class InvestRewardsPump implements PumpInterface
{
    use ArrayPumpTrait;
    use DatabasePumpTrait;
    use DoctrineLoggablePumpTrait;
    use InvestsPumpTrait;

    /** @var array<string, int> */
    private array $userCache = [];

    /** @var array<string, int> */
    private array $chargeCache = [];

    /** @var array<string, int> */
    private array $rewardCache = [];

    public function __construct(
        private RewardRepository $rewardRepository,
        private PumpedUserRepository $userRepository,
        private ChargeRepository $chargeRepository,
    ) {
        $this->setFlushBatchSize(8);
    }

    public function supports(mixed $sample): bool
    {
        if ($this->hasAllKeys($sample, ['invest', 'reward', 'fulfilled'])) {
            return true;
        }

        return false;
    }

    public function pump(mixed $record, array $context): void
    {
        $reward = $this->getReward($record);
        if ($reward === null) {
            return;
        }

        $invest = $this->getInvest($record, $context);
        if ($invest === null) {
            return;
        }

        $charge = $this->getCharge($invest);
        if ($charge === null) {
            return;
        }

        $user = $this->getUser($invest);
        if ($user === null) {
            return;
        }

        if ($charge->getStatus() !== ChargeStatus::InCharge) {
            return;
        }

        $claim = new RewardClaim();
        $claim->setOwner($user);
        $claim->setCharge($charge);

        $reward->addClaim($claim);
        $claim->setReward($reward);

        $status = match ($record['fulfilled']) {
            0 => RewardClaimStatus::InPending,
            1 => RewardClaimStatus::Fulfilled,
        };

        $claim->setStatus($status);

        $address = $this->getAddress($claim, $context);
        $claim->setAddress($address);

        $this->persist($claim, $context);
    }

    private function getInvest(array $record, array $context): ?array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `invest` i WHERE i.id = :invest'
        );

        $query->execute(['invest' => $record['invest']]);

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return $result;
    }

    private function getReward(array $record): ?Reward
    {
        $id = $record['reward'];

        if (isset($this->rewardCache[$id])) {
            return $this->rewardRepository->find($this->rewardCache[$id]);
        }

        $reward = $this->rewardRepository->findOneBy(['migratedId' => $id]);
        if (!$reward) {
            return null;
        }

        $this->rewardCache[$id] = $reward->getId();

        return $reward;
    }

    private function getCharge(array $invest): ?Charge
    {
        $id = $invest['id'];

        if (isset($this->chargeCache[$id])) {
            return $this->chargeRepository->find($this->chargeCache[$id]);
        }

        $charge = $this->chargeRepository->findOneBy(['migratedId' => $id]);
        if (!$charge) {
            return null;
        }

        $this->chargeCache[$id] = $charge->getId();

        return $charge;
    }

    private function getUser(array $record): ?User
    {
        return $this->userRepository->findPumped($record['user']);
    }

    private function getAddress(RewardClaim $claim, array $context): ?Address
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `invest_address` a WHERE a.invest = :invest'
        );

        $query->execute(['invest' => $claim->getCharge()->getMigratedId()]);

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$result || $result['name'] === null) {
            return null;
        }

        [$firstName, $lastName] = UserService::guessNames($result['name']);

        $address = new Address();
        $address->setUser($claim->getOwner());
        $address->setFirstName($firstName);
        $address->setLastName($lastName);
        $address->setLine1($result['address']);
        $address->setCity($result['location']);
        $address->setPostCode($result['zipcode']);
        $address->setCountry($result['country']);

        return $address;
    }
}
