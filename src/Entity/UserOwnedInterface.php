<?php

namespace App\Entity;

use App\Entity\User\User;

interface UserOwnedInterface
{
    /**
     * @return User|null The User who is the owner of this entity
     */
    public function getOwner(): ?User;

    /**
     * @param User|null $owner The User who is the owner of this entity, or null to remove ownership
     */
    public function setOwner(?User $owner): static;

    /**
     * Determines if the given User is the owner of this entity.
     *
     * @param User $user The User to check ownership against
     */
    public function isOwnedBy(User $user): bool;
}
