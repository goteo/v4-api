<?php

namespace App\Money\Totalization\Totalizer;

use App\Money\Money;
use App\Money\MoneyService;
use App\Money\Totalization\TotalizedMoney;
use App\Money\Totalization\TotalizerInterface;

/**
 * Provides totalization for iterations of Money like array elements.
 */
class MoneyArrayTotalizer implements TotalizerInterface
{
    public function __construct(
        private MoneyService $moneyService,
    ) {}

    public static function getSupportedResource(): string
    {
        return self::class;
    }

    /**
     * @param iterable<int, array{amount: int, currency: string}> $items An iterable of `\App\Money\Money` like arrays
     */
    public function totalize(iterable $items): TotalizedMoney
    {
        $length = 0;
        $money = new Money(0, $this->moneyService->getDefaultCurrency());

        foreach ($items as $charge) {
            ++$length;
            $money = $this->moneyService->add(new Money($charge['amount'], $charge['currency']), $money);
        }

        return new TotalizedMoney(
            $money->getAmount(),
            $money->getCurrency(),
            $length
        );
    }
}
