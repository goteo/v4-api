<?php

namespace App\State\Project;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Project\Support;
use App\Money\Totalization\TotalizedMoney;
use App\Money\Totalization\Totalizer\MoneyArrayTotalizer;
use App\State\QueryBuilderExtractor;

/**
 * Totalizes the money raised by a filtered Project collection.
 *
 * Reuses the same ApiFilters declared on the Project collection by re-applying every registered
 * ORM collection extension, then totalizes the money of the ProjectSupports of the matching Projects.
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
            ->select('o.id');

        // Projects are matched in a subquery so to-many filter joins (categories, themes) can't duplicate supports
        $supports = $projects->getEntityManager()->createQueryBuilder()
            ->select('s.money.amount AS amount')
            ->addSelect('s.money.currency AS currency')
            ->from(Support::class, 's')
            ->where(\sprintf('s.project IN (%s)', $projects->getDQL()))
            ->andWhere('s.money.amount IS NOT NULL')
            ->andWhere('s.money.currency IS NOT NULL')
            ->setParameters($projects->getParameters());

        return $this->moneyArrayTotalizer->totalize($supports->getQuery()->getArrayResult());
    }
}
