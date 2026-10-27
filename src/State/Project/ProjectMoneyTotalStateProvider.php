<?php

namespace App\State\Project;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Accounting\Accounting;
use App\Money\Totalization\TotalizedMoney;
use App\Money\Totalization\Totalizer\MoneyArrayTotalizer;
use App\State\QueryBuilderExtractor;

/**
 * Totalizes the money raised by a filtered Project collection.
 *
 * Reuses the same ApiFilters declared on the Project collection by re-applying every registered
 * ORM collection extension, then totalizes the balance of the Accountings of the matching Projects.
 */
class ProjectMoneyTotalStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBuilderExtractor $queryBuilderExtractor,
        private MoneyArrayTotalizer $moneyArrayTotalizer,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TotalizedMoney
    {
        $projects = $this->queryBuilderExtractor
            ->getQueryBuilder($operation, $context, fn($e) => !$e instanceof PaginationExtension)
            ->resetDQLPart('orderBy')
            ->select('IDENTITY(o.accounting)');

        // Projects are matched in a subquery so to-many filter joins (categories, themes) can't duplicate accountings
        $accountings = $projects->getEntityManager()->createQueryBuilder()
            ->select('a.balance.amount AS amount')
            ->addSelect('a.balance.currency AS currency')
            ->from(Accounting::class, 'a')
            ->where(\sprintf('a.id IN (%s)', $projects->getDQL()))
            ->andWhere('a.balance.amount IS NOT NULL')
            ->andWhere('a.balance.currency IS NOT NULL')
            ->setParameters($projects->getParameters());

        return $this->moneyArrayTotalizer->totalize($accountings->getQuery()->getArrayResult());
    }
}
