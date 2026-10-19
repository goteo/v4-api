<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Money\Money;
use App\Money\MoneyService;
use App\Money\Totalization\TotalizedMoney;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Totalizes the embedded `money` of a filtered resource collection.
 *
 * Re-applies the collection filters as an `IN` subquery and sums by currency in the database,
 * so to-many filter joins can't duplicate rows and entities are never hydrated.
 */
class MoneyTotalStateProvider implements ProviderInterface
{
    use EntityOperationStateTrait;

    public function __construct(
        private QueryBuilderExtractor $queryBuilderExtractor,
        private EntityManagerInterface $entityManager,
        private MoneyService $moneyService,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TotalizedMoney
    {
        $filtered = $this->queryBuilderExtractor
            ->getQueryBuilder($operation, $context, fn($e) => !$e instanceof PaginationExtension)
            ->resetDQLPart('orderBy')
            ->select('o.id');

        $totals = $this->entityManager->createQueryBuilder()
            ->select('SUM(t.money.amount) AS amount')
            ->addSelect('t.money.currency AS currency')
            ->addSelect('COUNT(t.id) AS length')
            ->from($this->getEntityClass($operation), 't')
            ->where(\sprintf('t.id IN (%s)', $filtered->getDQL()))
            ->andWhere('t.money.amount IS NOT NULL')
            ->andWhere('t.money.currency IS NOT NULL')
            ->groupBy('t.money.currency')
            ->setParameters($filtered->getParameters())
            ->getQuery()
            ->getArrayResult();

        $length = 0;
        $money = new Money(0, $this->moneyService->getDefaultCurrency());
        foreach ($totals as $total) {
            $length += (int) $total['length'];
            $money = $this->moneyService->add(new Money((int) $total['amount'], $total['currency']), $money);
        }

        return new TotalizedMoney($money->getAmount(), $money->getCurrency(), $length);
    }
}
