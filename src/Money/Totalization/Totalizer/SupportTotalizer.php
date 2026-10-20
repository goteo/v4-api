<?php

namespace App\Money\Totalization\Totalizer;

use App\Entity\Project\Support;
use App\Money\Money;
use App\Money\MoneyService;
use App\Money\Totalization\TotalizedMoney;
use App\Money\Totalization\TotalizerInterface;

class SupportTotalizer implements TotalizerInterface
{
    public function __construct(
        private MoneyService $moneyService,
    ) {}

    public static function getSupportedResource(): string
    {
        return Support::class;
    }

    /**
     * @param iterable<int, Support> $items
     */
    public function totalize(iterable $items): TotalizedMoney
    {
        $length = 0;
        $money = new Money(0, $this->moneyService->getDefaultCurrency());

        foreach ($items as $support) {
            ++$length;
            $money = $this->moneyService->add($support->getMoney(), $money);
        }

        return new TotalizedMoney(
            $money->getAmount(),
            $money->getCurrency(),
            $length
        );
    }
}
