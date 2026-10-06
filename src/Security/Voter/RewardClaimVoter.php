<?php

namespace App\Security\Voter;

use App\ApiResource\Project\RewardClaimApiResource;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class RewardClaimVoter extends Voter
{
    use UserOwnedVoterTrait;

    public const EDIT = 'CLAIM_EDIT';
    public const VIEW = 'CLAIM_VIEW';
    public const OWNS = 'CLAIM_OWNS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::OWNS])
            && $subject instanceof RewardClaimApiResource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        switch ($attribute) {
            case self::VIEW:
                return true;
            case self::EDIT:
                return $this->voteEdit($subject, $token->getUser());
            case self::OWNS:
                return $this->voteOwns($subject, $token->getUser());
        }

        return false;
    }

    private function voteEdit(RewardClaimApiResource $claim, ?UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        return $this->isOwnerOf($claim->reward->project, $user);
    }

    private function voteOwns(RewardClaimApiResource $claim, ?UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        return $this->isOwnerOf($claim->reward, $user);
    }
}
