<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Money\Totalization\TotalizedMoney;
use App\Money\Totalization\TotalizerLocator;

class MoneyTotalStateProvider implements ProviderInterface
{
    use EntityOperationStateTrait;

    public function __construct(
        private QueryBuilderExtractor $queryBuilderExtractor,
        private TotalizerLocator $totalizerLocator,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TotalizedMoney
    {
        $entityClass = $this->getEntityClass($operation);
        $queryBuilder = $this->queryBuilderExtractor->getQueryBuilder($operation, $context, fn($e) => !$e instanceof PaginationExtension);

        $totalizer = $this->totalizerLocator->get($entityClass);

        return $totalizer->totalize($queryBuilder->getQuery()->toIterable());
    }
}
