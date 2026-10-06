<?php

namespace App\Service\Matchfunding;

use App\Entity\Accounting\Transaction;
use App\Entity\EmbeddableMoney;
use App\Entity\Gateway\Charge;
use App\Entity\Matchfunding\MatchAgainst;
use App\Entity\Matchfunding\MatchCallSubmissionStatus;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectDeadline;
use App\Matchfunding\Formula\FormulaLocator;
use App\Matchfunding\Rule\RuleLocator;
use App\Repository\Matchfunding\MatchStrategyRepository;
use App\Service\Project\BudgetService;

class MatchfundingService
{
    public const SUBMISSION_ACCEPTED = MatchCallSubmissionStatus::Accepted;

    public function __construct(
        private RuleLocator $ruleLocator,
        private FormulaLocator $formulaLocator,
        private BudgetService $budgetService,
        private MatchStrategyRepository $matchStrategyRepository,
    ) {}

    /**
     * Perform the match-making logic for a Charge.
     *
     * @return Transaction[] The Transactions from the MatchCalls that should be made for a matcheable Charge
     */
    public function match(Charge $charge): array
    {
        $target = $charge->getTarget()->getOwner();

        if (!$target instanceof Project) {
            return [];
        }

        $transactions = [];

        foreach ($target->getMatchCallSubmissionsBy(self::SUBMISSION_ACCEPTED) as $submission) {
            $rankedStrategies = $this->matchStrategyRepository->findByCall($submission->getCall());

            foreach ($rankedStrategies as $strategy) {
                foreach ($this->ruleLocator->getFrom($strategy) as $rule) {
                    if (!$rule->validate($charge, $submission)) {
                        // If an strategy's rule fails, skip to the next strategy
                        continue 2;
                    }
                }

                $toBeMatched = match ($strategy->getAgainst()) {
                    MatchAgainst::Charge => $charge->getMoney(),
                    MatchAgainst::BudgetMin => $this->getBudget($target)[ProjectDeadline::Minimum->value],
                    MatchAgainst::BudgetOpt => $this->getBudget($target)[ProjectDeadline::Optimum->value],
                };

                $matched = $this->formulaLocator
                    ->get($strategy->getFormulaName())
                    ->match($strategy->getFactor(), $toBeMatched, $strategy->getLimit());

                $transaction = new Transaction();
                $transaction->setMoney(EmbeddableMoney::of($matched));
                $transaction->setOrigin($submission->getCall()->getAccounting());
                $transaction->setTarget($submission->getProject()->getAccounting());

                $transactions[] = $transaction;

                continue;
            }
        }

        return $transactions;
    }

    private function getBudget(Project $project)
    {
        return $this->budgetService->calcBudget(
            $project->getBudgetItems()->toArray(),
            $project->getAccounting()->getCurrency()
        );
    }
}
