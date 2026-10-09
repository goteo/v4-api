<?php

namespace App\Security\Voter;

use App\ApiResource\Project\ReviewAreaApiResource;
use App\Entity\Project\ReviewArea;
use App\Entity\User\User;
use App\Mapping\AutoMapper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ReviewAreaVoter extends Voter
{
    use UserOwnedVoterTrait;

    public const EDIT = 'REVIEWAREA_EDIT';
    public const VIEW = 'REVIEWAREA_VIEW';

    public function __construct(
        private AutoMapper $mapper,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof ReviewAreaApiResource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        /** @var ReviewArea */
        $area = $this->mapper->map($subject, ReviewArea::class);
        if (!$area || !$area instanceof ReviewArea) {
            return false;
        }

        $project = $area->getReview()->getProject();

        switch ($attribute) {
            case self::EDIT:
                return $area->getReview()->isReviewedBy($user);
            case self::VIEW:
                return $this->isOwnerOf($project, $user);
        }

        return false;
    }
}
