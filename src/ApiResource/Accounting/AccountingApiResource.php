<?php

namespace App\ApiResource\Accounting;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\MoneyOutput;
use App\Entity\Accounting\Accounting;
use App\Mapping\Transformer\AccountingOwnerMapTransformer;
use App\State\Accounting\AccountingStateProcessor;
use App\State\Accounting\AccountingStateProvider;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;

/**
 * v4 features an advanced economy model under the hood.
 * Accountings are implemented as a common interface for issuing and receiving Transactions,
 * which allows different resources to have money-capabalities.
 * \
 * \
 * Many different actions can trigger changes in Accountings, such as GatewayCheckouts being successfully charged.
 */
#[API\ApiResource(
    shortName: 'Accounting',
    stateOptions: new Options(entityClass: Accounting::class),
    provider: AccountingStateProvider::class,
    processor: AccountingStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Get()]
class AccountingApiResource
{
    #[API\ApiProperty(identifier: true)]
    public int $id;

    /**
     * IRI of the resource owning this Accounting.
     */
    #[MapFrom(Accounting::class, transformer: AccountingOwnerMapTransformer::class)]
    public string $owner;

    #[MapTo(Accounting::class, 'owner')]
    #[MapFrom(Accounting::class, 'owner')]
    #[API\ApiProperty(readable: false)]
    public object $ownerObject;

    /**
     * The preferred currency for monetary operations.\
     * 3-letter ISO 4217 currency code.
     */
    public string $currency;

    #[API\ApiProperty(security: 'is_granted("ACCOUNTING_VIEW", object)')]
    public MoneyOutput $balance;
}
