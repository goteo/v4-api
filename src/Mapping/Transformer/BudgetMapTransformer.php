<?php

namespace App\Mapping\Transformer;

use App\ApiResource\MoneyOutput;
use App\ApiResource\Project\Budget;
use App\ApiResource\Project\BudgetSummary;
use App\Entity\Project\BudgetItem;
use App\Entity\Project\BudgetItemType;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Service\Project\BudgetService;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class BudgetMapTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private BudgetService $budgetService,
    ) {}

    /**
     * @param Project $source
     */
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $items = $source->getBudgetItems()->toArray();
        $currency = $source->getAccounting()->getCurrency();

        $budget = new Budget();
        $budget->minimum = $this->getItemsSummary($items, $currency, ProjectDeadline::Minimum);
        $budget->optimum = $this->getItemsSummary($items, $currency, ProjectDeadline::Optimum);

        return $budget;
    }

    private function filterItemsByType(array $items, BudgetItemType $type)
    {
        return \array_filter($items, function (BudgetItem $item) use ($type) {
            return $item->getType() === $type;
        });
    }

    private function getItemsSummary(array $items, string $currency, ProjectDeadline $deadline)
    {
        $deadline = $deadline->value;

        $summary = new BudgetSummary();

        $summary->money = MoneyOutput::of($this->budgetService->calcBudget(
            $items,
            $currency
        )[$deadline]);

        $summary->task = MoneyOutput::of($this->budgetService->calcBudget(
            $this->filterItemsByType($items, BudgetItemType::Task),
            $currency
        )[$deadline]);

        $summary->material = MoneyOutput::of($this->budgetService->calcBudget(
            $this->filterItemsByType($items, BudgetItemType::Material),
            $currency
        )[$deadline]);

        $summary->infra = MoneyOutput::of($this->budgetService->calcBudget(
            $this->filterItemsByType($items, BudgetItemType::Infrastructure),
            $currency
        )[$deadline]);

        return $summary;
    }
}
