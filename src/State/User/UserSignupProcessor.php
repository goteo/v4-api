<?php

namespace App\State\User;

use ApiPlatform\Metadata as API;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\User\UserApiResource;
use App\Dto\UserSignupDto;
use App\Entity\User\Organization;
use App\Entity\User\User;
use App\Entity\User\UserType;
use App\Mapping\AutoMapper;
use App\Service\UserService;
use App\State\EntityStateProcessor;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserSignupProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private EntityStateProcessor $entityStateProcessor,
        private UserService $userService,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    /**
     * @param UserSignupDto $data
     *
     * @return UserApiResource
     */
    public function process(mixed $data, API\Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User */
        $user = $this->autoMapper->map($data, User::class);

        $user->setHandle($this->userService->sequentializeHandle($data->email));
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $data->password));

        if ($user->isType(UserType::Organization)) {
            $user->setOrganization(Organization::for($user));
        }

        $user = $this->entityStateProcessor->process($user, $operation, $uriVariables, $context);

        return $this->autoMapper->map($user, UserApiResource::class);
    }
}
