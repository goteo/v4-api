<?php

namespace App\Service;

use App\ApiResource\Accounting\AccountingBalancePoint;
use App\ApiResource\MoneyOutput;
use App\Entity\Accounting\Accounting;
use App\Entity\Accounting\Transaction;
use App\Entity\User\User;
use App\Gateway\Wallet\WalletService;
use App\Money\Money;
use App\Money\MoneyInterface;
use App\Money\MoneyService;
use App\Repository\Accounting\TransactionRepository;

class AccountingService
{
    public function __construct(
        private MoneyService $money,
        private WalletService $wallet,
        private TransactionRepository $transactionRepository,
    ) {}

    public function calcBalance(Accounting $accounting): Money
    {
        $owner = $accounting->getOwner();

        if ($owner instanceof User) {
            return $this->wallet->getBalance($accounting);
        }

        $trxs = $this->transactionRepository->findForBalanceCalc($accounting);

        $accountingId = $accounting->getId();
        $balance = new Money(0, $accounting->getCurrency());
        foreach ($trxs as $trx) {
            $transaction = new Money($trx['money.amount'], $trx['money.currency']);

            if ($trx['target_id'] === $accountingId) {
                $balance = $this->money->add($transaction, $balance);
            }

            if ($trx['origin_id'] === $accountingId) {
                $balance = $this->money->substract($transaction, $balance);
            }
        }

        return $balance;
    }

    /**
     * Returns a list of transactions that are in the given period.
     *
     * @param Transaction[] $transactions
     *
     * @return Transaction[]
     */
    private function filterTransactions(
        array $transactions,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array {
        return \array_filter($transactions, fn(Transaction $trx) => $trx->getDateCreated() >= $start && $trx->getDateCreated() < $end);
    }

    private function createPoint(
        \DateTimeInterface $lowerBound,
        \DateTimeInterface $upperBound,
        MoneyInterface $balance,
        array $transactions,
    ): AccountingBalancePoint {
        $point = new AccountingBalancePoint();
        $point->start = $lowerBound;
        $point->end = $upperBound;
        $point->balance = MoneyOutput::of($balance);
        $point->length = count($transactions);

        return $point;
    }

    /**
     * Calculates an AccountingBalancePoint series for a given Accounting over a period of time.
     *
     * @param Accounting  $accounting The Accounting to calc the data series for
     * @param \DatePeriod $period     A period of time for the desired transactions and time unit of the serie
     * @param bool        $aggregate  if true, balances accumulate over time; otherwise, they are interval-specific
     *
     * @return array<int, AccountingBalancePoint> One AccountingBalancePoint for each interval inside the date period range
     */
    public function calcBalancePoints(
        Accounting $accounting,
        \DatePeriod $period,
        bool $aggregate = false,
    ): array {
        $trxs = $this->transactionRepository->findByAccounting(
            $accounting,
            $period->getStartDate(),
            $period->getEndDate(),
        );

        $points = [];
        $totalBalance = new Money(0, $accounting->getCurrency());

        $accountingId = $accounting->getId();
        foreach ($period as $start) {
            $end = \DateTime::createFromInterface($start)->add($period->getDateInterval());
            $periodTrxs = $this->filterTransactions($trxs, $start, $end);

            $balance = $aggregate ? $totalBalance : new Money(0, $accounting->getCurrency());
            foreach ($periodTrxs as $trx) {
                $trxMoney = $trx->getMoney();

                if ($trx->getTarget()->getId() === $accountingId) {
                    $balance = $this->money->add($trxMoney, $balance);
                }

                if ($trx->getOrigin()->getId() === $accountingId) {
                    $balance = $this->money->substract($trxMoney, $balance);
                }
            }

            if ($aggregate) {
                $totalBalance = $balance;
            }

            $points[] = $this->createPoint($start, $end, $balance, $periodTrxs);
        }

        return $points;
    }
}
