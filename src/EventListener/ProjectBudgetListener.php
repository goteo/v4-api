<?php

namespace App\EventListener;

use App\Entity\Project\BudgetItem;
use App\Entity\Project\Project;
use App\Service\Project\BudgetService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Keeps the denormalized budget of a Project in sync with its BudgetItems.
 */
#[AsDoctrineListener(event: Events::onFlush)]
class ProjectBudgetListener
{
    public function __construct(
        private BudgetService $budgetService,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();

        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();

        $isItem = fn(object $entity) => $entity instanceof BudgetItem;

        /** @var BudgetItem[] $saved */
        $saved = \array_filter([...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates()], $isItem);
        /** @var BudgetItem[] $deleted */
        $deleted = \array_filter($uow->getScheduledEntityDeletions(), $isItem);

        /** @var array<int, Project> $projects */
        $projects = [];

        foreach ([...$saved, ...$deleted] as $item) {
            // Items moved to another Project change the budget of the previous one too
            $previous = $uow->getEntityChangeSet($item)['project'][0] ?? null;

            foreach ([$item->getProject(), $previous] as $project) {
                if ($project instanceof Project) {
                    $projects[\spl_object_id($project)] = $project;
                }
            }
        }

        foreach ($projects as $project) {
            if ($uow->isScheduledForDelete($project)) {
                continue;
            }

            // The collection in the Project is not aware of the items being flushed
            $items = [];
            foreach ([...$project->getBudgetItems(), ...$saved] as $item) {
                $items[\spl_object_id($item)] = $item;
            }

            $items = \array_filter(
                $items,
                fn(BudgetItem $item) => $item->getProject() === $project && !\in_array($item, $deleted, true)
            );

            $project->setBudget($this->budgetService->summarize(
                $items,
                $project->getAccounting()->getCurrency()
            ));

            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Project::class), $project);
        }
    }
}
