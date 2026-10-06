<?php

namespace App\Security\Voter;

use App\ApiResource\Project\ProjectApiResource;
use App\Entity\Project\ProjectStatus;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProjectVoter extends Voter
{
    use UserOwnedVoterTrait;

    public const EDIT = 'PROJECT_EDIT';
    public const VIEW = 'PROJECT_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof ProjectApiResource;
    }

    /**
     * @param ProjectApiResource $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($subject, $user);
            case self::VIEW:
                return true;
        }

        return false;
    }

    private function canEdit(ProjectApiResource $project, ?User $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        if (!$this->isOwnerOf($project, $user)) {
            return false;
        }

        return in_array($project->status, [
            ProjectStatus::InDraft,
            ProjectStatus::InCampaignReviewToChange,
            ProjectStatus::ToCampaign,
            ProjectStatus::InCampaign,
        ]);
    }
}
