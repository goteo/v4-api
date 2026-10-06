<?php

namespace App\Service\Project;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\ReviewType;
use App\Entity\User\User;
use App\Security\Voter\UserOwnedVoterTrait;

class ProjectService
{
    use UserOwnedVoterTrait;

    public const OWNER_VALID_TRANSITIONS = [
        ProjectStatus::InDraft->value => [ProjectStatus::ToCampaignReview],
        ProjectStatus::InCampaignReviewToChange->value => [ProjectStatus::InCampaignReviewToReview],
        ProjectStatus::ToCampaign->value => [ProjectStatus::InCampaign],
        ProjectStatus::InFundingReviewToChange->value => [ProjectStatus::InFundingReviewToReview],
        ProjectStatus::ToFunding->value => [ProjectStatus::InFunding],
    ];

    public function __construct(
        private ReviewService $reviewService,
        private CalendarService $calendarService,
    ) {}

    /**
     * Check if an User can move a Project to some status.
     *
     * @param User          $actor   The User that is to apply the status change
     * @param Project       $project The Project in their current (pre-change) state
     * @param ProjectStatus $to      The ProjectStatus that the actor wishes to move the Project to
     */
    public function canTransition(User $actor, Project $project, ProjectStatus $to): bool
    {
        if ($actor->hasRoles(['ROLE_ADMIN'])) {
            return true;
        }

        if (!$this->isOwnerOf($project, $actor)) {
            return false;
        }

        $from = $project->getStatus();

        if (
            array_key_exists($from->value, self::OWNER_VALID_TRANSITIONS)
            && in_array($to, self::OWNER_VALID_TRANSITIONS[$from->value])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Moves a Project to a given ProjectStatus.
     *
     * @param ProjectStatus $to The new status to where the Project will move
     *
     * @return Project The resulting Project
     */
    public function transition(Project $project, ProjectStatus $to): Project
    {
        $from = $project->getStatus();

        if ($from === $to) {
            return $project;
        }

        $project->setStatus($to);

        return match ([$from, $to]) {
            [ProjectStatus::InDraft, ProjectStatus::ToCampaignReview] => $project->addReview($this->reviewService->makeReview(ReviewType::Campaign)),

            [ProjectStatus::ToCampaign, ProjectStatus::InCampaign] => $project->setCalendar($this->calendarService->makeCalendar($project->getDeadline())),

            default => $project,
        };
    }
}
