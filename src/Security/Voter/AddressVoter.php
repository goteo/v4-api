<?php

namespace App\Security\Voter;

use App\ApiResource\AddressApiResource;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AddressVoter extends Voter
{
    use UserOwnedVoterTrait;

    public const EDIT = 'ADDRESS_EDIT';
    public const VIEW = 'ADDRESS_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof AddressApiResource;
    }

    /**
     * @param AddressApiResource $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        switch ($attribute) {
            case self::EDIT:
            case self::VIEW:
                return $this->isOwnerOf($subject->user, $user);
        }

        return false;
    }
}
