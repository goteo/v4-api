<?php

namespace App\State\User;

use ApiPlatform\Metadata as API;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\User\UserApiResource;
use App\Dto\User\UserUpdationDto;
use App\Entity\User\Organization;
use App\Entity\User\User;
use App\Entity\User\UserType;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityStateProcessor $entityProcessor,
    ) {}

    /**
     * @param UserUpdationDto $data
     *
     * @return UserUpdationDto
     */
    public function process(mixed $data, API\Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var UserApiResource */
        $resource = $this->autoMapper->map($data, $context['previous_data']);
        /** @var User */
        $user = $this->autoMapper->map($resource, User::class);

        if (isset($data->password)) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $data->password));
        }

        if ($user->isType(UserType::Organization) && $user->getOrganization() === null) {
            $user->setOrganization(Organization::for($user));
        }

        $user = $this->entityProcessor->process($user, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        return $this->autoMapper->map($user, UserApiResource::class);
    }
}
