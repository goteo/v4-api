<?php

namespace App\OAuth2\Server;

use App\Entity\User\User;
use League\Bundle\OAuth2ServerBundle\Converter\UserConverterInterface;
use League\Bundle\OAuth2ServerBundle\Entity\User as OAuth2User;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsDecorator(UserConverterInterface::class)]
final class UserConverter implements UserConverterInterface
{
    public function toLeague(UserInterface $user): UserEntityInterface
    {
        if (!$user instanceof User) {
            throw new \LogicException(sprintf(
                'Expected %s, got %s.',
                User::class,
                $user::class,
            ));
        }

        $userEntity = new OAuth2User();
        $userEntity->setIdentifier((string) $user->getId());

        return $userEntity;
    }
}
