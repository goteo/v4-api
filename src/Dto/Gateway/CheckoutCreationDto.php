<?php

namespace App\Dto\Gateway;

use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\Gateway\GatewayApiResource;
use App\Entity\Gateway\Checkout;
use App\Gateway\RefundStrategy;
use App\Mapping\Transformer\GatewayIdMapTransformer;
use App\Validator\ChargeToProjectInCampaign;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutCreationDto
{
    /**
     * The desired Gateway to checkout with.
     */
    #[Assert\NotBlank()]
    #[MapFrom(property: 'gatewayId', transformer: GatewayIdMapTransformer::class)]
    #[MapTo(property: 'gatewayId', transformer: 'source.gateway.id')]
    public GatewayApiResource $gateway;

    /**
     * The Accounting paying for the charges.
     */
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("ACCOUNTING_EDIT", object.origin)')]
    #[Assert\NotBlank()]
    public AccountingApiResource $origin;

    /**
     * A list of the payment items to be charged to the origin.
     *
     * @var ChargeCreationDto[]
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
}
