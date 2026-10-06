<?php

namespace App\State\Accounting;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Accounting\AccountingApiResource;
use App\Mapping\AutoMapper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AccountingStateProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: ItemProvider::class)]
        private ProviderInterface $itemProvider,
        #[Autowire(service: CollectionProvider::class)]
        private ProviderInterface $collectionProvider,
        private AutoMapper $autoMapper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $accountings = $this->collectionProvider->provide($operation, $uriVariables, $context);
            $resources = $this->autoMapper->mapCollection($accountings, AccountingApiResource::class);

            return new TraversablePaginator(
                new \ArrayIterator($resources),
                $accountings->getCurrentPage(),
                $accountings->getItemsPerPage(),
                $accountings->getTotalItems()
            );
        }

        $accounting = $this->itemProvider->provide($operation, $uriVariables, $context);

        if ($accounting === null) {
            return null;
        }

        return $this->autoMapper->map($accounting, AccountingApiResource::class);
    }
}
