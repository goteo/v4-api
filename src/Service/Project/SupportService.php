<?php

namespace App\Service\Project;

use App\Entity\Accounting\Accounting;
use App\Entity\Accounting\Transaction;
use App\Entity\EmbeddableMoney;
use App\Entity\Project\Project;
use App\Entity\Project\Support;
use App\Money\Money;
use App\Money\MoneyService;
use App\Repository\Project\SupportRepository;

/**
 * Helpers to work with `App\Entity\Project\Support` entities.
 */
class SupportService
{
    public function __construct(
        private SupportRepository $supportRepository,
        private MoneyService $moneyService,
    ) {}

    /**
     * Obtain a Support (new or existing) for a Project and origin.
     */
    public function getSupport(
        Project $project,
        Accounting $origin,
    ): Support {
        /** @var Support|null $support */
        $support = $this->supportRepository->findOneBy([
            'project' => $project,
            'origin' => $origin,
        ]);

        if ($support === null) {
            $support = new Support();
            $support->setProject($project);
            $support->setOrigin($origin);
        }

        return $support;
    }

    /**
     * Add new Transactions and recalculate the Money for an existing Support.
     *
     * @param Transaction[] $transactions
     */
    public function withTransactions(Support $support, array $transactions): Support
    {
        foreach ($transactions as $transaction) {
            $support->addTransaction($transaction);
        }

        $money = new Money(0, $support->getProject()->getAccounting()->getCurrency());
        foreach ($support->getTransactions() as $transaction) {
            $money = $this->moneyService->add($transaction->getMoney(), $money);
        }

        $support->setMoney(EmbeddableMoney::of($money));

        return $support;
    }
}
