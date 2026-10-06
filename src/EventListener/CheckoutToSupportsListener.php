<?php

namespace App\EventListener;

use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Entity\Project\Support;
use App\Gateway\CheckoutStatus;
use App\Service\Project\SupportService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class CheckoutToSupportsListener
{
    public function __construct(
        private SupportService $supportService,
    ) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();

        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Checkout) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            if (!isset($changeSet['status'])) {
                continue;
            }

            [, $newStatus] = $changeSet['status'];

            if ($newStatus !== CheckoutStatus::Charged->value) {
                continue;
            }

            $this->updateSupports($em, $entity);
        }
    }

    private function updateSupports(EntityManagerInterface $em, Checkout $checkout): void
    {
        $origin = $checkout->getOrigin();

        /** @var Charge[] $charges */
        $charges = $checkout->getCharges()->toArray();

        /** @var array<int|string, mixed> $projects */
        $projects = [];

        /** @var array<int|string, Transaction[]> $transactionsByProject */
        $transactionsByProject = [];

        foreach ($charges as $charge) {
            $project = $charge->getTarget()->getProject();
            if ($project === null) {
                continue;
            }

            $projectId = $project->getId();

            $projects[$projectId] = $project;

            $transactionsByProject[$projectId] ??= [];

            foreach ($charge->getTransactions() as $transaction) {
                $transactionsByProject[$projectId][] = $transaction;
            }
        }

        $uow = $em->getUnitOfWork();
        $metadata = $em->getClassMetadata(Support::class);

        foreach ($projects as $projectId => $project) {
            $support = $this->supportService->getSupport($project, $origin);
            $support = $this->supportService->withTransactions($support, $transactionsByProject[$projectId] ?? []);

            $em->persist($support);
            $uow->computeChangeSet($metadata, $support);
        }
    }
}
