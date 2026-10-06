<?php

namespace App\Gateway\Gateway;

use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Gateway\GatewayInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CashGateway implements GatewayInterface
{
    public static function getId(): string
    {
        return 'cash';
    }

    public static function getName(): string
    {
        return 'Cash';
    }

    public static function getSupportedChargeTypes(): array
    {
        return [];
    }

    public static function getAllowedRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function process(Checkout $checkout): Checkout
    {
        return $checkout;
    }

    public function refund(Charge $charge): Charge
    {
        return $charge;
    }

    public function handleRedirect(Request $request): RedirectResponse
    {
        return new Response();
    }

    public function handleWebhook(Request $request): Response
    {
        return new Response();
    }
}
