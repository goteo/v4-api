<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Mapping\AutoMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ApiResourceStateProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: CollectionProvider::class)]
        private ProviderInterface $collectionProvider,
        #[Autowire(service: ItemProvider::class)]
        private ProviderInterface $itemProvider,
        private AutoMapper $autoMapper,
        private ManagerRegistry $managerRegistry,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceClass = $operation->getClass();

        if ($operation instanceof CollectionOperationInterface) {
            $collection = $this->collectionProvider->provide($operation, $uriVariables, $context);
            $this->preload([...$collection], $operation->getStateOptions()?->getEntityClass(), $resourceClass);

            $resources = $this->autoMapper->mapCollection($collection, $resourceClass);

            return new TraversablePaginator(
                new \ArrayIterator($resources),
                $collection->getCurrentPage(),
                $collection->getItemsPerPage(),
                $collection->getTotalItems()
            );
        }

        $item = $this->itemProvider->provide($operation, $uriVariables, $context);

        if (!$item) {
            return null;
        }

        return $this->autoMapper->map($item, $resourceClass);
    }

    /**
     * Loads in batches the associations that the mapping is going to read,
     * instead of letting Doctrine lazy-load them one entity at a time.
     *
     * @param object[] $entities
     */
    private function preload(array $entities, ?string $entityClass, string $resourceClass): void
    {
        $em = $entityClass ? $this->managerRegistry->getManagerForClass($entityClass) : null;

        if (!$entities || !$em instanceof EntityManagerInterface) {
            return;
        }

        $metadata = $em->getClassMetadata($entityClass);

        foreach ($metadata->getAssociationNames() as $association) {
            if (!\property_exists($resourceClass, $association)) {
                continue;
            }

            if ($metadata->isCollectionValuedAssociation($association)) {
                $em->createQuery(\sprintf(
                    'SELECT e, a FROM %s e LEFT JOIN e.%s a WHERE e IN (:entities)',
                    $entityClass,
                    $association
                ))->setParameter('entities', $entities)->getResult();

                continue;
            }

            $targetClass = $metadata->getAssociationTargetClass($association);
            $targetMetadata = $em->getClassMetadata($targetClass);

            $ids = [];
            foreach ($entities as $entity) {
                $target = $metadata->getFieldValue($entity, $association);

                if ($target !== null && $em->isUninitializedObject($target)) {
                    $ids[] = $em->getUnitOfWork()->getSingleIdentifierValue($target);
                }
            }

            if ($ids) {
                $em->getRepository($targetClass)->findBy([
                    $targetMetadata->getSingleIdentifierFieldName() => \array_unique($ids),
                ]);
            }
        }
    }
}
