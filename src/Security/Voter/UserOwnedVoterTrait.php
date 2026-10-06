<?php

namespace App\Security\Voter;

use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\Project\ProjectApiResource;
use App\ApiResource\User\UserApiResource;
use App\Entity\User\User;
use App\Entity\UserOwnedInterface;

trait UserOwnedVoterTrait
{
    /**
     * Determines if the given User is the owner of the resource.
     *
     * @param object $subject A resource that might or might not be owned by the User
     * @param ?User  $user    The User to check ownership against
     *
     * @return bool `false` if ownership could not be guaranteed
     */
    public function isOwnerOf(object $subject, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($subject instanceof UserApiResource) {
            return $subject->id === $user->getId();
        }

        if ($subject instanceof AccountingApiResource) {
            return $subject->id === $user->getAccounting()->getId();
        }

        if ($subject instanceof ProjectApiResource) {
            return $subject->owner->id === $user->getId();
        }

        if ($subject instanceof UserOwnedInterface) {
            return $subject->isOwnedBy($user);
        }

        if (!\property_exists($subject, 'owner')) {
            return false;
        }

        if ($subject->owner instanceof UserApiResource) {
            return $subject->owner->id === $user->getId();
        }

        return false;
    }
}
