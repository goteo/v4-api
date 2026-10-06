<?php

namespace App\ApiResource\Project;

use App\ApiResource\MoneyOutput;

class BudgetSummary
{
    /**
     * The total money by the included items.
     */
    public MoneyOutput $money;

    /**
     * The total money of type 'task'.
     */
    public MoneyOutput $task;

    /**
     * The total money of type 'material'.
     */
    public MoneyOutput $material;

    /**
     * The total money of type 'infrastructure'.
     */
    public MoneyOutput $infra;
}
