<?php

namespace App\ApiResource\Accounting;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\MoneyOutput;
use App\Entity\Accounting\Transaction;
use App\State\ApiResourceStateProvider;

/**
 * AccountingTransactions represent a movement of money from one Accounting (origin) into another (target).\
 * \
 * When a transaction targets an Accounting it means that the Accounting receives it, this will add to that Accounting.
 * When a transaction originates from an Accounting the Accounting issues the transaction and it will deduct from it if,
 * specially when the transaction comes from a GatewayCharge processed by the `wallet` Gateway.\
 * \
 * AccountingTransactions are generated for each GatewayCharge in a GatewayCheckout once it becomes successful.
 */
#[API\ApiResource(
    shortName: 'AccountingTransaction',
    stateOptions: new Options(entityClass: Transaction::class),
    provider: ApiResourceStateProvider::class
)]
#[API\GetCollection()]
#[API\Get()]
class TransactionApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The monetary value received at target and issued at origin.
     */
    public MoneyOutput $money;

    /**
     * The Accounting from which the Transaction comes from.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $origin;

    /**
     * The Accounting where the Transaction goes to.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $target;
}
