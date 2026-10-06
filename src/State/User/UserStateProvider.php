<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\User\UserApiResource;
use App\Mapping\AutoMapper;
use App\Repository\User\UserRepository;

class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private AutoMapper $autoMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $idOrHandle = $uriVariables['idOrHandle'];

        $user = $this->userRepository->findOneByIdOrHandle($idOrHandle);

        if ($user === null) {
            return null;
        }

        return $this->autoMapper->map($user, UserApiResource::class);
    }
}
