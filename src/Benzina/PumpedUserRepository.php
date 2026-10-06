<?php

namespace App\Benzina;

use App\Entity\User\User;
use App\Repository\User\UserRepository;

class PumpedUserRepository extends UserRepository
{
    /** @var array<string, int> */
    private array $userCache = [];

    public function findPumped(string $id): ?User
    {
        if (isset($this->userCache[$id])) {
            return $this->find($this->userCache[$id]);
        }

        $user = $this->findOneBy(['migratedId' => $id]);
        if (!$user) {
            $user = $this->findDeduped([$id]);
        }

        if (!$user) {
            return null;
        }

        $this->userCache[$id] = $user->getId();

        return $user;
    }
}
