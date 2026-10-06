<?php

namespace App\EventListener;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\Review;
use App\Entity\Project\ReviewType;
use App\Service\Project\CalendarService;
use App\Service\Project\ReviewService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

/**
 * Listens for status changes in Projects to apply side-effects for those statuses.
 */
#[AsDoctrineListener(Events::onFlush)]
final class ProjectStatusListener
{
    public function __construct(
        private CalendarService $calendarService,
        private ReviewService $reviewService,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Project) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            if (!isset($changeSet['status'])) {
                continue;
            }

            [$oldStatus, $newStatus] = $changeSet['status'];

            $oldStatus = ProjectStatus::from($oldStatus);
            $newStatus = ProjectStatus::from($newStatus);

            match ([$oldStatus, $newStatus]) {
                [ProjectStatus::ToCampaign, ProjectStatus::InCampaign] => $this->statusInCampaign($entity, $uow, $em),
                [ProjectStatus::InDraft, ProjectStatus::ToCampaignReview] => $this->statusToCampaignReview($entity, $uow, $em),
                default => null,
            };
        }
    }

    private function statusInCampaign(Project $project, UnitOfWork $uow, EntityManagerInterface $em)
    {
        $calendar = $this->calendarService->makeCalendar($project->getDeadline());
        $project->setCalendar($calendar);
    }

    private function statusToCampaignReview(Project $project, UnitOfWork $uow, EntityManagerInterface $em)
    {
        $review = $this->reviewService->makeReview(ReviewType::Campaign);
        $project->addReview($review);

        $em->persist($review);
        $uow->computeChangeSet(
            $em->getClassMetadata(Review::class),
            $review
        );
    }
}
