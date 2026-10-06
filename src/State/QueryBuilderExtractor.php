<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class QueryBuilderExtractor
{
    use EntityOperationStateTrait;

    /**
     * @param iterable<QueryCollectionExtensionInterface> $collectionExtensions
     */
    public function __construct(
        private readonly iterable $collectionExtensions,
        private ManagerRegistry $managerRegistry,
    ) {}

    /**
     * Get the QueryBuilder behind a given API operation.
     *
     * @param callable(QueryCollectionExtensionInterface): bool|null $extensionFilter A function
     *
     * @return QueryBuilder The query builder for the given params
     */
    public function getQueryBuilder(
        Operation $operation,
        array $context = [],
        ?callable $extensionFilter = null,
    ): QueryBuilder {
        $entityClass = $this->getEntityClass($operation);
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        $repository = $manager->getRepository($entityClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        $extensionFilter ??= static fn($extension) => true;
        foreach ($this->getCollectionExtensions() as $extension) {
            if (!$extensionFilter($extension)) {
                continue;
            }

            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $entityClass, $operation, $context);
        }

        return $queryBuilder;
    }

    /**
     * Get the collection extensions injected by default.
     *
     * @return iterable<QueryCollectionExtensionInterface>
     */
    public function getCollectionExtensions(): iterable
    {
        return $this->collectionExtensions;
    }
}
