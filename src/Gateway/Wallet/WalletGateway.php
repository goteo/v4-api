<?php

namespace App\Gateway\Wallet;

use App\Entity\Accounting\Transaction;
use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Gateway\ChargeStatus;
use App\Gateway\ChargeType;
use App\Gateway\CheckoutStatus;
use App\Gateway\GatewayInterface;
use App\Money\Money;
use App\Money\MoneyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WalletGateway implements GatewayInterface
{
    public static function getId(): string
    {
        return 'wallet';
    }

    public static function getName(): string
    {
        return 'Wallet';
    }

    public static function getSupportedChargeTypes(): array
    {
        return [
            ChargeType::Single,
        ];
    }

    public static function getAllowedRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function __construct(
        private WalletService $wallet,
        private MoneyService $money,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * In Wallet process means the money spent will be moved out of the balance of the origin.
     * No actual payment is issued here as the money already exists in-wallet.
     *
     * {@inheritdoc}
     */
    public function process(Checkout $checkout): Checkout
    {
        $origin = $checkout->getOrigin();
        $available = $this->wallet->getBalance($origin);

        if ($this->money->isLess($available, $this->getChargeTotal($checkout))) {
            throw new \Exception("Can't spend more than what you have!");
        }

        $charges = $checkout->getCharges();
        foreach ($charges as $charge) {
            $transaction = new Transaction();
            $transaction->setMoney($charge->getMoney());
            $transaction->setOrigin($origin);
            $transaction->setTarget($charge->getTarget());

            $expenditure = $this->wallet->spend($transaction);

            $this->entityManager->persist($expenditure);
            $this->entityManager->flush();

            $charge->setStatus(ChargeStatus::InCharge);
            $charge->addTransaction($transaction);
        }

        $checkout->setStatus(CheckoutStatus::Charged);

        $this->entityManager->persist($checkout);
        $this->entityManager->flush();

        return $checkout;
    }

    /**
     * In Wallet refund means the money re-imbursed will be moved into the balance of the origin.
     * No actual refund is issued here as the money shall remain in-wallet.
     *
     * {@inheritdoc}
     */
    public function refund(Charge $charge): Charge
    {
        $checkout = $charge->getCheckout();

        $transaction = new Transaction();
        $transaction->setMoney($charge->getMoney());
        $transaction->setOrigin($charge->getTarget());
        $transaction->setTarget($checkout->getOrigin());

        $income = $this->wallet->save($transaction);

        $charge->setStatus(ChargeStatus::Walleted);
        $charge->addTransaction($transaction);

        $this->entityManager->persist($income);
        $this->entityManager->persist($charge);
        $this->entityManager->flush();

        return $charge;
    }

    public function handleRedirect(Request $request): RedirectResponse
    {
        return new RedirectResponse('');
    }

    public function handleWebhook(Request $request): Response
    {
        return new Response();
    }

    private function getChargeTotal(Checkout $checkout): Money
    {
        $total = new Money(0, $checkout->getOrigin()->getCurrency());

        $charges = $checkout->getCharges();
        foreach ($charges as $charge) {
            $total = $this->money->add($charge->getMoney(), $total);
        }

        return $total;
    }
}
