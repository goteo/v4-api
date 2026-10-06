<?php

namespace App\Dto\Gateway;

use App\Money\Totalization\TotalizedMoney;

/**
 * Aggregate totals for a filtered GatewayCharge collection.
 */
class ChargesTotalsDto
{
    public function __construct(
        private int $projects,
        private TotalizedMoney $money,
    ) {}

    /**
     * The number of distinct Projects targeted by the charges matching the filter set.
     */
    public function getProjects(): int
    {
        return $this->projects;
    }

    /**
     * The aggregated monetary value in the charges.
     */
    public function getMoney(): TotalizedMoney
    {
        return $this->money;
    }
}
