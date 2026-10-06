<?php

namespace App\ApiResource\Accounting;

use ApiPlatform\Metadata as API;
use ApiPlatform\Metadata\QueryParameter;
use App\ApiResource\MoneyOutput;
use App\State\Accounting\AccountingBalancePointStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AccountingBalancePoints represent a dated aggregate Accounting balance.\
 * \
 * Each point is the sum of money at incoming and outgoing transactions in an Accounting for a period of time,
 * you can query multiple balance points to obtain the evolution of the Accounting's balance over time.
 */
#[API\ApiResource(shortName: 'AccountingBalancePoint')]
#[API\GetCollection(
    provider: AccountingBalancePointStateProvider::class,
    parameters: [
        'accounting' => new QueryParameter(
            required: true,
            schema: ['type' => 'string', 'format' => 'iri-reference'],
        ),
        'start' => new QueryParameter(
            required: true,
            schema: ['type' => 'string', 'format' => 'date-time'],
        ),
        'interval' => new QueryParameter(
            schema: ['type' => 'string', 'default' => '24h'],
            constraints: [new Assert\Regex([
                'pattern' => '/^\d+(h|d|w)$/',
                'message' => 'Must be a number followed by h, d, or w (e.g. 1h, 24h, 2d, 1w)',
            ])],
        ),
        'end' => new QueryParameter(
            schema: ['type' => 'string', 'format' => 'date-time', 'default' => 'now'],
        ),
        'aggregate' => new QueryParameter(
            schema: ['type' => 'boolean', 'default' => false],
            description: 'If true, the balance points are cumulative over time.',
        ),
    ],
)]
class AccountingBalancePoint
{
    /**
     * The start date for items aggregated in this point.
     */
    public \DateTimeInterface $start;

    /**
     * The end date for items aggregated in this point. Not inclusive.
     */
    public \DateTimeInterface $end;

    /**
     * Resulting balance for items in this point.
     */
    public MoneyOutput $balance;

    /**
     * The number of items aggregated in this point.
     */
    public int $length;
}
