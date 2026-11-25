<?php

namespace App\Mapping\Transformer;

use App\ApiResource\MoneyOutput;
use App\ApiResource\Project\Budget;
use App\ApiResource\Project\BudgetSummary;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectBudget;
use App\Entity\Project\ProjectDeadline;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

/**
 * Builds the Budget of a Project from the denormalized data stored with it.
 *
 * @see \App\EventListener\ProjectBudgetListener
 */
class BudgetMapTransformer implements PropertyTransformerInterface
{
    /**
     * @param Project $source
     */
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $stored = $source->getBudget();
        $currency = $stored->currency ?? $source->getAccounting()->getCurrency();

        $budget = new Budget();
        $budget->minimum = $this->getSummary($stored, ProjectDeadline::Minimum, $currency);
        $budget->optimum = $this->getSummary($stored, ProjectDeadline::Optimum, $currency);

        return $budget;
    }

    private function getSummary(ProjectBudget $stored, ProjectDeadline $deadline, string $currency): BudgetSummary
    {
        $summary = new BudgetSummary();

        foreach (['money', 'task', 'material', 'infra'] as $key) {
            $amount = $stored->{$deadline->value.\ucfirst($key)} ?? 0;
            $summary->$key = new MoneyOutput($amount, $currency, null);
        }

        return $summary;
    }
}
