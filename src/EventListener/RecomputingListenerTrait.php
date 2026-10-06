<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;

trait RecomputingListenerTrait
{
    /**
     * Appropriately updates the Doctrine's Unit of Work and marks entities for persistence.
     */
    private function computeChangeSet(EntityManagerInterface $em, object $entity): void
    {
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata($entity::class);

        $em->persist($entity);
        $uow->computeChangeSet($meta, $entity);
    }
}
