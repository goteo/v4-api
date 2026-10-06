<?php

namespace App\EventListener;

use App\Entity\Gateway\Charge;
use App\Gateway\ChargeStatus;
use App\Gateway\GatewayLocator;
use App\Gateway\Wallet\WalletGateway;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(
    event: Events::preUpdate,
    method: 'preUpdate',
    entity: Charge::class
)]
class ChargeRefundListener
{
    private const FIELD_STATUS = 'status';

    public function __construct(
        private GatewayLocator $gatewayLocator,
        private WalletGateway $walletGateway,
    ) {}

    public function preUpdate(Charge $charge, PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField(self::FIELD_STATUS)) {
            return;
        }

        if (
            $args->getOldValue(self::FIELD_STATUS) === ChargeStatus::InCharge->value
        ) {
            return;
        }

        $charge = match ($charge->getStatus()) {
            default => $charge,
            ChargeStatus::ToRefund => $this->processGatewayRefund($charge),
            ChargeStatus::ToWallet => $this->processWalletRefund($charge),
        };
    }

    private function processGatewayRefund(Charge $charge): Charge
    {
        $gateway = $this->gatewayLocator->getForCheckout($charge->getCheckout());

        $charge = $gateway->refund($charge);
        $charge->setStatus(ChargeStatus::Refunded);

        return $charge;
    }

    private function processWalletRefund(Charge $charge): Charge
    {
        $charge = $this->walletGateway->refund($charge);
        $charge->setStatus(ChargeStatus::Walleted);

        return $charge;
    }
}
