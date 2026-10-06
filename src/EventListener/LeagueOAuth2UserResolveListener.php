<?php

namespace App\EventListener;

use App\Repository\User\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LeagueOAuth2UserResolveListener
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    #[AsEventListener(event: OAuth2Events::USER_RESOLVE)]
    public function onUserResolve(UserResolveEvent $event): void
    {
        $user = $this->userRepository->findOneByIdentifier($event->getUsername());

        if (!$user) {
            return;
        }

        if (!$this->userPasswordHasher->isPasswordValid($user, $event->getPassword())) {
            return;
        }

        $event->setUser($user);
    }
}
