<?php

namespace App\Benzina;

use App\Entity\Accounting\Transaction;
use App\Entity\EmbeddableMoney;
use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Entity\Gateway\Tracking;
use App\Entity\Project\Project;
use App\Entity\Project\Reward;
use App\Entity\Project\Support;
use App\Entity\Tipjar;
use App\Entity\User\User;
use App\Gateway\ChargeStatus;
use App\Gateway\ChargeType;
use App\Gateway\CheckoutStatus;
use App\Gateway\Gateway\CashGateway;
use App\Gateway\Gateway\CecaGateway;
use App\Gateway\Gateway\DropGateway;
use App\Gateway\Paypal\PaypalGateway;
use App\Gateway\Stripe\StripeGateway;
use App\Gateway\Wallet\WalletGateway;
use App\Money\Money;
use App\Money\MoneyService;
use App\Repository\Project\ProjectRepository;
use App\Repository\Project\RewardRepository;
use App\Repository\Project\SupportRepository;
use App\Repository\TipjarRepository;
use Goteo\Benzina\Pump\ArrayPumpTrait;
use Goteo\Benzina\Pump\PumpInterface;

class InvestsPump implements PumpInterface
{
    use ArrayPumpTrait;
    use DatabasePumpTrait;
    use DoctrineLoggablePumpTrait;
    use InvestsPumpTrait;

    public const TRACKING_TITLE_V3 = 'v3 Invest ID';
    public const TRACKING_TITLE_PAYMENT = 'v3 Invest Payment';
    public const TRACKING_TITLE_TRANSACTION = 'v3 Invest Transaction';
    public const TRACKING_TITLE_PREAPPROVAL = 'v3 Invest Preapproval';

    public const CHARGE_TITLE_PROJECT = 'Pago en Goteo v3 - Donación a proyecto';
    public const CHARGE_TITLE_POOL = 'Pago en Goteo v3 - Carga de monedero';
    public const CHARGE_TITLE_TIP = 'Pago en Goteo v3 - Propina a la plataforma';

    private const PLATFORM_RETURN_URL = 'https://goteo.org';
    private const PLATFORM_TIPJAR_NAME = 'platform';

    private const MAX_INT = 2147483647;
    private const CURRENCY = 'EUR';

    private ?int $tipjarCache = null;

    /** @var array<string, int> */
    private array $projectCache = [];

    /** @var array<string, int> */
    private array $supportCache = [];

    /** @var array<string, int> */
    private array $rewardCache = [];

    public function __construct(
        private PumpedUserRepository $userRepository,
        private ProjectRepository $projectRepository,
        private SupportRepository $supportRepository,
        private TipjarRepository $tipjarRepository,
        private RewardRepository $rewardRepository,
        private MoneyService $moneyService,
    ) {
        $this->setFlushBatchSize(1);
    }

    public function supports(mixed $sample): bool
    {
        if ($this->hasAllKeys($sample, self::INVEST_KEYS)) {
            return true;
        }

        return false;
    }

    public function pump(mixed $record, array $context): void
    {
        if (!$record['user'] || empty($record['user'])) {
            return;
        }

        if (!$record['amount'] || $record['amount'] < 1) {
            return;
        }

        if (!$record['method'] || empty($record['method'])) {
            return;
        }

        $user = $this->getUser($record);
        if ($user === null) {
            return;
        }

        $project = $this->getProject($record);
        $tipjar = $this->getPlatformTipjar();

        $now = new \DateTime();
        $invested = new \DateTime($record['datetime'] ?? $record['invested']);

        $checkout = new Checkout();
        $checkout->setMigrated(true);
        $checkout->setMigratedId($record['id']);
        $checkout->setDateCreated($invested);
        $checkout->setDateUpdated($now);

        $checkout->setOrigin($user->getAccounting());
        $checkout->setStatus($this->getCheckoutStatus($record));
        $checkout->setGatewayId($this->getCheckoutGatewayId($record));
        $checkout->setReturnUrl(self::PLATFORM_RETURN_URL);

        foreach ($this->getCheckoutTrackings($record) as $tracking) {
            $checkout->addTracking($tracking);
        }

        $charge = new Charge();
        $charge->setMigrated(true);
        $charge->setMigratedId($record['id']);
        $charge->setDateCreated($invested);
        $charge->setDateUpdated($now);
        $charge->setStatus($this->getChargeStatus($record));
        $charge->setType($this->getChargeType($record));
        $charge->setMoney($this->getChargeMoney($record['amount'], self::CURRENCY));

        if ($project === null) {
            $charge->setTitle(self::CHARGE_TITLE_POOL);
            $charge->setTarget($user->getAccounting());
        } else {
            $charge->setTitle(self::CHARGE_TITLE_PROJECT);
            $charge->setTarget($project->getAccounting());
        }

        $checkout->addCharge($charge);

        if ($record['donate_amount'] > 0) {
            $tip = new Charge();
            $tip->setMigrated(true);
            $tip->setMigratedId($record['id']);
            $tip->setDateCreated($invested);
            $tip->setDateUpdated($now);
            $tip->setStatus($this->getChargeStatus($record));
            $tip->setType(ChargeType::Single);
            $tip->setTitle(self::CHARGE_TITLE_TIP);
            $tip->setMoney($this->getChargeMoney($record['donate_amount'], self::CURRENCY));
            $tip->setTarget($tipjar->getAccounting());

            $checkout->addCharge($tip);
        }

        foreach ($checkout->getCharges() as $charge) {
            if (!\in_array($charge->getStatus(), [ChargeStatus::InCharge, ChargeStatus::Refunded])) {
                continue;
            }

            $transaction = new Transaction();
            $transaction->setDateCreated($invested);
            $transaction->setMoney($charge->getMoney());
            $transaction->setOrigin($checkout->getOrigin());
            $transaction->setTarget($charge->getTarget());

            $charge->addTransaction($transaction);

            if (
                $charge->getTarget()->getOwner() instanceof Project
                && $charge->getStatus() === ChargeStatus::InCharge
            ) {
                $support = $this->getSupport($charge);
                $support->setProject($charge->getTarget()->getOwner());
                $support->setOrigin($checkout->getOrigin());
                $support->addTransaction($transaction);
                $support->setMoney($this->calcSupportMoney($support));
                $support->setAnonymous($this->getSupportAnon($record, $support));
                $support->setMessage($this->getSupportMessage($record, $support, $context));

                $this->entityManager->persist($support);
            }

            if ($charge->getStatus() !== ChargeStatus::Refunded) {
                continue;
            }

            $returned = new Transaction();
            $returned->setDateCreated(new \DateTime($record['returned'] ?? 'now'));
            $returned->setMoney($charge->getMoney());
            $returned->setOrigin($charge->getTarget());
            $returned->setTarget($checkout->getOrigin());

            $charge->addTransaction($returned);
        }

        $this->persist($checkout, $context);
    }

    private function getUser(array $record): ?User
    {
        return $this->userRepository->findPumped($record['user']);
    }

    private function getProject(array $record): ?Project
    {
        $id = $record['project'] ?? null;

        if (!$id) {
            return null;
        }

        if (isset($this->projectCache[$id])) {
            return $this->projectRepository->find($this->projectCache[$id]);
        }

        $project = $this->projectRepository->findOneBy(['migratedId' => $id]);

        if (!$project) {
            return null;
        }

        $this->projectCache[$id] = $project->getId();

        return $project;
    }

    private function getPlatformTipjar(): Tipjar
    {
        if ($this->tipjarCache !== null) {
            return $this->tipjarRepository->find($this->tipjarCache);
        }

        $tipjar = $this->tipjarRepository->findOneBy(['name' => self::PLATFORM_TIPJAR_NAME]);

        if ($tipjar) {
            $this->tipjarCache = $tipjar->getId();

            return $tipjar;
        }

        $tipjar = new Tipjar();
        $tipjar->setName(self::PLATFORM_TIPJAR_NAME);

        $this->entityManager->persist($tipjar);
        $this->entityManager->flush();

        return $tipjar;
    }

    private function getSupport(Charge $charge): ?Support
    {
        $origin = $charge->getCheckout()->getOrigin();
        $target = $charge->getTarget()->getOwner();

        $originId = $origin->getId();
        $projectId = $target->getId();

        $cacheKey = $projectId.'-'.$originId;

        if (isset($this->supportCache[$cacheKey])) {
            return $this->supportRepository->find($this->supportCache[$cacheKey]);
        }

        $support = $this->supportRepository->findOneBy([
            'origin' => $origin,
            'project' => $target,
        ]);

        if ($support !== null) {
            $this->supportCache[$cacheKey] = $support->getId();

            return $support;
        }

        return new Support();
    }

    private function getSupportAnon(array $record, Support $support): bool
    {
        if ($record['anonymous'] === 1) {
            return true;
        }

        if ($support->isAnonymous()) {
            return true;
        }

        return false;
    }

    private function getSupportMessage(array $record, Support $support, array $context): ?string
    {
        $message = $support->getMessage();
        $msg = $this->getInvestMsg($record, $context);

        if (!$msg || empty($msg['msg'])) {
            return $message;
        }

        if (!$message) {
            return $msg['msg'];
        }

        return \sprintf("\n***\n%s", $msg['msg']);
    }

    private function calcSupportMoney(Support $support): EmbeddableMoney
    {
        $transactions = $support->getTransactions();
        if ($transactions->isEmpty()) {
            return EmbeddableMoney::of(new Money(0, self::CURRENCY));
        }

        $first = $transactions->first();
        $money = new Money(0, $first->getMoney()->getCurrency());

        foreach ($transactions as $transaction) {
            $money = $this->moneyService->add($transaction->getMoney(), $money);
        }

        return EmbeddableMoney::of($money);
    }

    private function getInvestMsg(array $record, array $context)
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `invest_msg` m WHERE m.invest = :invest'
        );

        $query->execute(['invest' => $record['id']]);

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    private function getChargeType(array $record): ChargeType
    {
        if (\in_array($record['method'], ['stripe_subscription'])) {
            return ChargeType::Recurring;
        }

        return ChargeType::Single;
    }

    private function getChargeMoney(int $amount, string $currency): EmbeddableMoney
    {
        $amount = $amount * 100;

        if ($amount >= self::MAX_INT) {
            $amount = self::MAX_INT;
        }

        return new EmbeddableMoney($amount, $currency);
    }

    private function getChargeStatus(array $record): ChargeStatus
    {
        switch ($record['status']) {
            case 0:
            case 1:
            case 3:
            case 7:
                if ($record['issue'] === 1) {
                    return ChargeStatus::ToCharge;
                }

                return ChargeStatus::InCharge;
            case 2:
            case 4:
            case 6:
                return ChargeStatus::Refunded;
            default:
                return ChargeStatus::ToCharge;
        }
    }

    private function getCheckoutStatus(array $record): CheckoutStatus
    {
        switch ($record['status']) {
            case 0:
            case 1:
            case 3:
            case 7:
                if ($record['issue'] === 1) {
                    return CheckoutStatus::ToCharge;
                }

                return CheckoutStatus::Charged;
            default:
                return CheckoutStatus::ToCharge;
        }
    }

    private function getCheckoutGatewayId(array $record): string
    {
        switch ($record['method']) {
            case 'tpv':
                return CecaGateway::getId();
            case 'paypal':
                return PaypalGateway::getId();
            case 'pool':
                return WalletGateway::getId();
            case 'cash':
                return CashGateway::getId();
            case 'drop':
                return DropGateway::getId();
            case 'stripe_subscription':
                return StripeGateway::getId();
            default:
                return '';
        }
    }

    /**
     * @return Tracking[]
     */
    private function getCheckoutTrackings(array $record): array
    {
        $v3Tracking = new Tracking();
        $v3Tracking->setTitle(self::TRACKING_TITLE_V3);
        $v3Tracking->setValue($record['id']);

        $trackings = [$v3Tracking];

        if (!empty($record['payment'])) {
            $payment = new Tracking();
            $payment->setTitle(self::TRACKING_TITLE_PAYMENT);
            $payment->setValue($record['payment']);

            $trackings[] = $payment;
        }

        if (!empty($record['transaction'])) {
            $transaction = new Tracking();
            $transaction->setTitle(self::TRACKING_TITLE_TRANSACTION);
            $transaction->setValue($record['transaction']);

            $trackings[] = $transaction;
        }

        if (!empty($record['preapproval'])) {
            $preapproval = new Tracking();
            $preapproval->setTitle(self::TRACKING_TITLE_PREAPPROVAL);
            $preapproval->setValue($record['preapproval']);

            $trackings[] = $preapproval;
        }

        return $trackings;
    }

    private function getInvestReward(array $record, array $context)
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `invest_reward` ir WHERE ir.invest = :invest'
        );

        $query->execute(['invest' => $record['id']]);

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    private function getReward(array $record, array $context): ?Reward
    {
        $invested = $this->getInvestReward($record, $context);

        if (!$invested) {
            return null;
        }

        $id = $invested['reward'];

        if (isset($this->rewardCache[$id])) {
            return $this->rewardRepository->find($this->rewardCache[$id]);
        }

        $reward = $this->rewardRepository->findOneBy(['migratedId' => $invested['reward']]);

        $this->rewardCache[$id] = $reward->getId();

        return $reward;
    }
}
