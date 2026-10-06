<?php

namespace App\Security\Voter;

use App\Entity\UserOwnedInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserOwnedVoter extends Voter
{
    use UserOwnedVoterTrait;

    /**
     * Exclusively grants access to the owner.
     *
     * This is intended for sensitive information that is meant for the owner only.
     * SHOULD NEVER BE OVERRIDEN BY ADMINS, MODERATORS, SUPER-ADMINS OR ANY HIGHER ROLE.
     */
    public const OWNED = 'USER_OWNED';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [self::OWNED])) {
            return true;
        }

        return false;
    }

    /**
     * @param UserOwnedInterface $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->isOwnerOf($subject, $token->getUser());
    }
}
