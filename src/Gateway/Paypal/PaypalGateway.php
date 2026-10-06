<?php

namespace App\Gateway\Paypal;

use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use App\Entity\Gateway\Tracking;
use App\Gateway\AbstractGateway;
use App\Gateway\ChargeType;
use App\Gateway\CheckoutStatus;
use App\Gateway\GatewayLink;
use App\Gateway\GatewayLinkType;
use Brick\Money\Money as BrickMoney;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://developer.paypal.com/studio/checkout/standard/integrate
 */
class PaypalGateway extends AbstractGateway
{
    public const PAYPAL_API_ADDRESS_LIVE = 'https://api-m.paypal.com';
    public const PAYPAL_API_ADDRESS_SANDBOX = 'https://api-m.sandbox.paypal.com';

    /** @see https://developer.paypal.com/docs/api/orders/v2/#orders_get!c=200&path=status&t=response */
    public const PAYPAL_STATUS_APPROVED = 'APPROVED';
    public const PAYPAL_STATUS_COMPLETED = 'COMPLETED';

    /** @see https://developer.paypal.com/api/rest/webhooks/event-names/#orders */
    public const PAYPAL_EVENT_ORDER_COMPLETED = 'CHECKOUT.ORDER.COMPLETED';
    public const PAYPAL_PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';

    /**
     * @see https://developer.paypal.com/docs/api/orders/v2/
     */
    private const PAYPAL_ORDER_INTENT = 'CAPTURE';

    public const TRACKING_TITLE_ORDER = 'PayPal Order ID';
    public const TRACKING_TITLE_TRANSACTION = 'PayPal Transaction ID';

    public function __construct(
        private PaypalService $paypal,
    ) {}

    public static function getId(): string
    {
        return 'paypal';
    }

    public static function getName(): string
    {
        return 'PayPal';
    }

    public static function getSupportedChargeTypes(): array
    {
        return [
            ChargeType::Single,
        ];
    }

    public static function getAllowedRoles(): array
    {
        return ['PUBLIC_ACCESS'];
    }

    public function process(Checkout $checkout): Checkout
    {
        $order = $this->paypal->postOrder([
            'intent' => self::PAYPAL_ORDER_INTENT,
            'purchase_units' => $this->getPaypalPurchaseUnits($checkout),
            'payment_source' => $this->getPaypalPaymentSource($checkout),
        ]);

        $tracking = new Tracking(self::TRACKING_TITLE_ORDER, $order['id']);

        $checkout->addTracking($tracking);

        foreach ($order['links'] as $linkData) {
            $linkType = \in_array($linkData['rel'], ['approve', 'payer-action'])
                ? GatewayLinkType::Payment
                : GatewayLinkType::Debug;

            $link = new GatewayLink();
            $link->url = $linkData['href'];
            $link->rel = $linkData['rel'];
            $link->method = $linkData['method'];
            $link->type = $linkType;

            $checkout->addLink($link);
        }

        return $checkout;
    }

    public function refund(Charge $charge): Charge
    {
        $trackings = $charge->getCheckout()->getTrackings();
        $captureTracking = $trackings->filter(function (Tracking $t) {
            return $t->getTitle() === self::TRACKING_TITLE_TRANSACTION && $t->getValue();
        })->current();

        if (!$captureTracking) {
            throw new \Exception('Tracking for PayPal capture ID not found.');
        }

        $captureId = $captureTracking->getValue();

        $response = $this->paypal->refundCapture($captureId, [
            'amount' => $this->getPaypalMoney($charge),
        ]);

        if (!in_array($response['status'], ['COMPLETED', 'PENDING'], true)) {
            throw new \Exception(sprintf('Refund failed. PayPal status: %s', $response['status']));
        }

        return $this->chargeService->addRefundTransaction($charge);
    }

    public function handleRedirect(Request $request): RedirectResponse
    {
        // TO-DO: handle non-success type redirect requests

        $checkout = $this->getAfterRedirectCheckout($request);
        $redirection = $this->getRedirectResponse($checkout);

        if ($checkout->getStatus() === CheckoutStatus::Charged) {
            return $redirection;
        }

        $orderId = $request->query->get('token');
        $order = $this->paypal->getOrder($orderId);

        if ($order['status'] !== self::PAYPAL_STATUS_APPROVED) {
            return $redirection;
        }

        $capture = $this->paypal->captureOrderPayment($order);
        if ($capture['status'] !== self::PAYPAL_STATUS_COMPLETED) {
            return $redirection;
        }

        foreach ($capture['purchase_units'] as $purchaseUnit) {
            $tracking = new Tracking(
                self::TRACKING_TITLE_TRANSACTION,
                $purchaseUnit['payments']['captures'][0]['id']
            );

            $checkout->addTracking($tracking);
        }

        $checkout = $this->checkoutService->chargeCheckout($checkout);

        $this->entityManager->persist($checkout);
        $this->entityManager->flush();

        return $redirection;
    }

    private function getWebhookSource(?string $userAgent): string
    {
        if (!$userAgent || !str_contains(strtolower($userAgent), 'paypal')) {
            return 'Unknown or custom client';
        }

        return 'PayPal';
    }

    private function createErrorResponse(string $message, Request $request, array $event = []): JsonResponse
    {
        $userAgent = $request->headers->get('User-Agent');

        return new JsonResponse([
            'status' => 'ERROR',
            'message' => $message,
            'eventId' => $event['id'] ?? 'N/A',
            'eventType' => $event['event_type'] ?? 'N/A',
            'source' => $this->getWebhookSource($userAgent),
            'userAgent' => $userAgent,
            'requestBody' => $request->getContent(),
            'requestHeaders' => $request->headers->all(),
        ], Response::HTTP_ACCEPTED);
    }

    public function handleWebhook(Request $request): Response
    {
        try {
            $event = $this->paypal->verifyWebhook($request);
        } catch (\Throwable $e) {
            return $this->createErrorResponse(
                sprintf('Webhook verification failed: %s', $e->getMessage()),
                $request
            );
        }

        $eventType = $event['event_type'];

        return match ($eventType ?? null) {
            self::PAYPAL_EVENT_ORDER_COMPLETED,
            self::PAYPAL_PAYMENT_CAPTURE_COMPLETED => $this->handleOrderCompleted($event),
            default => $this->createErrorResponse(
                sprintf('Unsupported webhook event type "%s".', $eventType ?? 'undefined'),
                $request,
                $event
            ),
        };
    }

    private function handleOrderCompleted(array $event): Response
    {
        $orderId = $event['resource']['supplementary_data']['related_ids']['order_id'] ?? null;

        $checkout = $this->checkoutRepository->findOneByTracking(self::TRACKING_TITLE_ORDER, $orderId);

        if ($checkout === null) {
            throw new \Exception(sprintf(
                "Could not find any Checkout by the Tracking '%s'",
                $orderId
            ), 1);
        }

        foreach ($event['resource']['purchase_units'] as $purchaseUnit) {
            $tracking = new Tracking(
                self::TRACKING_TITLE_TRANSACTION,
                $purchaseUnit['payments']['captures'][0]['id']
            );

            $checkout->addTracking($tracking);
        }

        $checkout = $this->checkoutService->chargeCheckout($checkout);

        return new JsonResponse(['checkout' => $checkout], Response::HTTP_NO_CONTENT);
    }

    private function getPaypalMoney(Charge $charge): array
    {
        $brick = BrickMoney::ofMinor(
            $charge->getMoney()->amount,
            $charge->getMoney()->currency
        );

        return [
            'value' => $brick->getAmount()->__toString(),
            'currency_code' => $brick->getCurrency()->getCurrencyCode(),
        ];
    }

    private function getPaypalPurchaseUnits(Checkout $checkout): array
    {
        $units = [];

        foreach ($checkout->getCharges() as $charge) {
            $money = $this->getPaypalMoney($charge);
            $reference = $this->checkoutService->generateTracking($checkout, $charge);

            $units[] = [
                'reference_id' => $reference,
                'custom_id' => $reference,
                'items' => [
                    [
                        'name' => $charge->getTitle(),
                        'description' => $charge->getDescription(),
                        'quantity' => '1',
                        'unit_amount' => [
                            ...$money,
                        ],
                    ],
                ],
                'amount' => [
                    ...$money,
                    'breakdown' => [
                        'item_total' => [
                            ...$money,
                        ],
                    ],
                ],
            ];
        }

        return $units;
    }

    private function getPaypalPaymentSource(Checkout $checkout): array
    {
        return [
            'paypal' => [
                'experience_context' => [
                    'return_url' => $this->checkoutService->generateRedirectUrl($checkout),
                ],
            ],
        ];
    }
}
