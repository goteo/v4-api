<?php

namespace App\ApiResource\Gateway;

use ApiPlatform\Doctrine\Orm\Filter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\MoneyOutput;
use App\Dto\Gateway\ChargesTotalsDto;
use App\Dto\Gateway\ChargeUpdationDto;
use App\Entity\Gateway\Charge;
use App\Filter\GatewayFilter;
use App\Gateway\ChargeStatus;
use App\Gateway\ChargeType;
use App\State\ApiResourceStateProvider;
use App\State\Gateway\ChargeStateProcessor;
use App\State\Gateway\ChargesTotalsStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Charge represents a payment item to be included in a Checkout for payment at a Gateway.
 */
#[API\ApiResource(
    shortName: 'GatewayCharge',
    stateOptions: new Options(entityClass: Charge::class),
    provider: ApiResourceStateProvider::class
)]
#[API\GetCollection()]
#[API\GetCollection(
    uriTemplate: '/gateway_charges/totals',
    provider: ChargesTotalsStateProvider::class,
    output: ChargesTotalsDto::class,
    paginationEnabled: false,
    openapi: new \ApiPlatform\OpenApi\Model\Operation(
        summary: 'Get charges totals',
        description: 'Returns a single ChargesTotals object with totalized metrics for the charges matching the same filters as the charges collection.',
        responses: [
            '200' => [
                'description' => 'Charges totals',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/GatewayCharge.ChargesTotalsDto',
                        ],
                    ],
                ],
            ],
        ]
    ),
)]
#[API\Get()]
#[API\Patch(
    input: ChargeUpdationDto::class,
    processor: ChargeStateProcessor::class,
)]
class ChargeApiResource
{
    #[API\ApiProperty(writable: false, identifier: true)]
    public ?int $id = null;

    /**
     * The Checkout to which this Charge item belongs to.
     */
    #[API\ApiFilter(GatewayFilter::class, properties: ['checkout.gateway'])]
    #[API\ApiFilter(Filter\SearchFilter::class, properties: ['checkout.origin'])]
    #[API\ApiFilter(Filter\SearchFilter::class, properties: ['checkout.trackings.value'])]
    public CheckoutApiResource $checkout;

    /**
     * How this item should be processed by the Gateway.\
     * \
     * `single` is for one time payments.\
     * `recurring` is for payments repeated over time.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(Filter\SearchFilter::class, strategy: 'exact')]
    public ChargeType $type = ChargeType::Single;

    /**
     * A short, descriptive string for this charge item.\
     * May be displayed to the payer.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(Filter\SearchFilter::class, strategy: 'exact')]
    public string $title;

    /**
     * Detailed information about the charge item.\
     * May be displayed to the payer.
     */
    public ?string $description = null;

    /**
     * The Accounting receiving the money after a successful payment.
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty(security: 'is_granted("ACCOUNTING_VIEW", object.target)')]
    #[API\ApiFilter(Filter\SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $target;

    /**
     * The money to-be-paid for this item at the Gateway.
     *
     * It is money before fees and taxes, not accountable.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(Filter\RangeFilter::class, properties: ['money.amount'])]
    #[API\ApiFilter(Filter\OrderFilter::class, properties: ['money.amount'])]
    #[API\ApiFilter(Filter\SearchFilter::class, properties: ['money.currency' => 'exact'])]
    public MoneyOutput $money;

    /**
     * The status of the charge item with the Gateway.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(Filter\SearchFilter::class, strategy: 'exact')]
    #[API\ApiFilter(Filter\OrderFilter::class, properties: ['status'])]
    public ChargeStatus $status = ChargeStatus::ToCharge;

    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(Filter\DateFilter::class)]
    #[API\ApiFilter(Filter\OrderFilter::class, properties: ['dateCreated' => 'DESC'])]
    public \DateTimeInterface $dateCreated;

    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(Filter\DateFilter::class)]
    #[API\ApiFilter(Filter\OrderFilter::class, properties: ['dateUpdated' => 'DESC'])]
    public \DateTimeInterface $dateUpdated;
}
