<?php

namespace App\State\Gateway;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Gateway\ChargesTotalsDto;
use App\Money\Totalization\Totalizer\MoneyArrayTotalizer;
use App\State\QueryBuilderExtractor;
use Doctrine\ORM\Query;

/**
 * Provides totalized metrics for a filtered GatewayCharge collection.
 *
 * Reuses the same ApiFilters declared on the GatewayCharge collection (target, status,
 * type, money range, dates, ...) by re-applying every registered ORM collection
 * extension, then runs a single COUNT(DISTINCT project) query.
 */
class ChargesTotalsStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBuilderExtractor $queryBuilderExtractor,
        private MoneyArrayTotalizer $moneyArrayTotalizer,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChargesTotalsDto
    {
        $projects = (int) $this->queryBuilderExtractor
            ->getQueryBuilder($operation, $context)
            ->resetDQLPart('orderBy')
            ->join('o.target', 'totals_target')
            ->join('totals_target.project', 'totals_project')
            ->select('COUNT(DISTINCT totals_project.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $money = $this->moneyArrayTotalizer
            ->totalize($this->queryBuilderExtractor
                ->getQueryBuilder($operation, $context, fn($e) => !$e instanceof PaginationExtension)
                ->resetDQLPart('select')
                ->select('o.money.amount AS amount')
                ->addSelect('o.money.currency AS currency')->getQuery()->toIterable([], Query::HYDRATE_ARRAY));

        return new ChargesTotalsDto($projects, $money);
    }
}
