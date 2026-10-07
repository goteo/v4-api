<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T of object
 *
 * @mixin ServiceEntityRepository
 */
trait FindByIdOrSlugTrait
{
    /**
     * @return T|null
     */
    public function findOneByIdOrSlug(mixed $idOrSlug): ?object
    {
        if (\ctype_digit((string) $idOrSlug)) {
            return $this->find($idOrSlug);
        }

        return $this->findOneBy(['slug' => $idOrSlug]);
    }

    public function getByIdOrSlugQuery(mixed $idOrSlug): QueryBuilder
    {
        /** @var QueryBuilder */
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->where(\ctype_digit((string) $idOrSlug) ? 'o.id = :idOrSlugField' : 'o.slug = :idOrSlugField');
        $queryBuilder->setParameter('idOrSlugField', $idOrSlug);

        return $queryBuilder;
    }
}
