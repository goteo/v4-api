<?php

namespace App\Repository;

use App\Entity\User\User;

trait DedupedTrait
{
    public function findDeduped(array $dedupedIds): ?User
    {
        /** @var \Doctrine\ORM\QueryBuilder */
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->where('u.deduped = 1')
            ->andWhere('JSON_CONTAINS(u.dedupedIds, :val) = 1')
            ->setParameter('val', \json_encode($dedupedIds))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
