<?php

namespace App\ApiResource\Gateway;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\Dto\Gateway\CheckoutCreationDto;
use App\Dto\Gateway\CheckoutUpdationDto;
use App\Entity\Gateway\Checkout;
use App\Filter\GatewayFilter;
use App\Gateway\CheckoutStatus;
use App\Gateway\GatewayLink;
use App\Gateway\RefundStrategy;
use App\Gateway\Tracking;
use App\Mapping\Transformer\GatewayIdMapTransformer;
use App\State\ApiResourceStateProvider;
use App\State\Gateway\CheckoutStateProcessor;
use App\Validator\ChargeToProjectInCampaign;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A GatewayCheckout represents a payment session with a Gateway.
 */
#[API\ApiResource(
    shortName: 'GatewayCheckout',
    stateOptions: new Options(entityClass: Checkout::class),
    provider: ApiResourceStateProvider::class,
    processor: CheckoutStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(
    input: CheckoutCreationDto::class,
    security: 'is_granted("ROLE_USER")'
)]
#[API\Get()]
#[API\Patch(
    input: CheckoutUpdationDto::class,
    security: 'is_granted("CHECKOUT_EDIT", object)'
)]
class CheckoutApiResource
{
    #[API\ApiProperty(writable: false, identifier: true)]
    public int $id;

    /**
     * The desired Gateway to checkout with.
     */
    #[Assert\NotBlank()]
    #[MapFrom(property: 'gatewayId', transformer: GatewayIdMapTransformer::class)]
    #[MapTo(property: 'gatewayId', transformer: 'source.gateway.id')]
    #[API\ApiFilter(GatewayFilter::class)]
    public GatewayApiResource $gateway;

    /**
     * The Accounting paying for the charges.
     */
    #[API\ApiProperty(security: 'is_granted("ACCOUNTING_VIEW", object.origin)')]
    #[API\ApiFilter(SearchFilter::class)]
    #[Assert\NotBlank()]
    public AccountingApiResource $origin;

    /**
     * A list of the payment items to be charged to the origin.
     *
     * @var ChargeApiResource[]
     */
    #[API\ApiProperty(readableLink: true, writableLink: true)]
    #[Assert\NotBlank()]
    #[Assert\Count(min: 1)]
    #[Assert\All([new ChargeToProjectInCampaign()])]
    public array $charges = [];

    /**
     * Gateways will redirect the user back to the v4 API,
     * which will then redirect the user to this address.\
     * \
     * An URL query param `checkoutId` with the Checkout ID value
     * will be appended on the redirection.
     */
    #[Assert\NotBlank()]
    #[Assert\Url()]
    public string $returnUrl;

    /**
     * The strategy chosen by the User to decide where the money will go to
     * in the event that one Charge needs to be returned.
     */
    #[MapTo(Checkout::class, property: 'refundStrategy')]
    #[MapFrom(Checkout::class, property: 'refundStrategy')]
    public RefundStrategy $refund = RefundStrategy::ToWallet;

    /**
     * The status of this Checkout, as confirmed by the Gateway.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class)]
    public CheckoutStatus $status = CheckoutStatus::ToCharge;

    /**
     * A list of related hyperlinks, as provided by the Gateway.
     *
     * @var GatewayLink[]
     */
    #[API\ApiProperty(writable: false)]
    #[MapTo(Checkout::class, transformer: [self::class, 'parseLinks'])]
    #[MapFrom(Checkout::class, transformer: [self::class, 'parseLinks'])]
    public array $links = [];

    /**
     * A list of related tracking codes and numbers, as provided by the Gateway.
     *
     * @var Tracking[]
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, properties: ['trackings.value'])]
    public array $trackings = [];

    #[API\ApiProperty(writable: false)]
    public \DateTimeInterface $dateCreated;

    #[API\ApiProperty(writable: false)]
    public \DateTimeInterface $dateUpdated;

    public static function parseLinks(array $values)
    {
        return \array_map(fn($value) => GatewayLink::tryFrom($value), $values);
    }
}
