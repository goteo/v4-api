<?php

namespace App\Service\Project;

use App\Entity\Project\BudgetItem;
use App\Entity\Project\BudgetItemType;
use App\Entity\Project\ProjectDeadline;
use App\Money\Money;
use App\Money\MoneyService;

class BudgetService
{
    public function __construct(
        private MoneyService $moneyService,
    ) {}

    /**
     * @param BudgetItem[] $items
     *
     * @return array{minimum: Money, optimum: Money}
     */
    public function calcBudget(array $items, string $currency): array
    {
        $totalMinimum = new Money(0, $currency);
        $totalOptimum = new Money(0, $currency);

        foreach ($items as $item) {
            $itemMoney = $item->getMoney();
            $itemDeadline = $item->getDeadline();

            if ($itemDeadline == ProjectDeadline::Minimum) {
                $totalMinimum = $this->moneyService->add($itemMoney, $totalMinimum);
            }

            $totalOptimum = $this->moneyService->add($itemMoney, $totalOptimum);
        }

        return [
            ProjectDeadline::Minimum->value => $totalMinimum,
            ProjectDeadline::Optimum->value => $totalOptimum,
        ];
    }

    /**
     * Calculates the totals of a budget, as a whole and by type of item.
     *
     * @param BudgetItem[] $items
     *
     * @return array{currency: string, minimum: array<string, int>, optimum: array<string, int>} Amounts in minor units
     */
    public function summarize(array $items, string $currency): array
    {
        $ofType = fn(BudgetItemType $type) => \array_filter($items, fn(BudgetItem $item) => $item->getType() === $type);

        $groups = [
            'money' => $items,
            'task' => $ofType(BudgetItemType::Task),
            'material' => $ofType(BudgetItemType::Material),
            'infra' => $ofType(BudgetItemType::Infrastructure),
        ];

        $summary = ['currency' => $currency];

        foreach ($groups as $key => $group) {
            foreach ($this->calcBudget($group, $currency) as $deadline => $money) {
                $summary[$deadline][$key] = $money->getAmount();
            }
        }

        return $summary;
    }
}
