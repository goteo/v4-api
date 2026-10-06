<?php

namespace App\Entity;

use App\Entity\User\User;

/**
 * @property ?User $owner
 */
trait UserOwnedTrait
{
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->getOwner()?->getId() === $user->getId();
    }
}
