<?php

namespace App\Gateway\Gateway;

use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Gateway\ChargeType;
use App\Gateway\GatewayInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CecaGateway implements GatewayInterface
{
    public const LEGACY_MESSAGE = 'This Gateway implementation is only meant to give support to legacy-records. DO NOT USE. DO NOT IMPLEMENT.';

    public static function getId(): string
    {
        return 'tpv';
    }

    public static function getName(): string
    {
        return 'Credit Card';
    }

    public static function getSupportedChargeTypes(): array
    {
        return [
            ChargeType::Single,
        ];
    }

    public static function getAllowedRoles(): array
    {
        // This Gateway only exists for historical purposes
        // DO NOT USE
        // DO NOT IMPLEMENT
        return [''];
    }

    public function process(Checkout $checkout): Checkout
    {
        throw new \Exception(self::LEGACY_MESSAGE);
    }

    public function refund(Charge $charge): Charge
    {
        throw new \Exception(self::LEGACY_MESSAGE);
    }

    public function handleRedirect(Request $request): RedirectResponse
    {
        throw new \Exception(self::LEGACY_MESSAGE);
    }

    public function handleWebhook(Request $request): Response
    {
        throw new \Exception(self::LEGACY_MESSAGE);
    }
}
