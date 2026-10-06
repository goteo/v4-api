<?php

namespace App\Gateway;

use App\Entity\Gateway\Charge;
use App\Entity\Gateway\Checkout;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gateway interfaces are in charge of connecting with payment gateways to send them the data in `Checkout` instances.\
 * \
 * The flow is:
 * 1. A client app creates a `Checkout` record
 * 2. `App\State\GatewayCheckoutProcessor` calls the `sendData` method of the respective interface for the new `Checkout`
 * 3. The interface connects with the gateway and sends the data for the `Checkout`
 * 4. The interface updates the `Checkout` with the checkout url and reference given by the gateway for the checkout session
 * 5. `App\State\GatewayCheckoutProcessor` returns the response data to the client app
 * 6. The client app redirects the user to the payment with the gateway
 * 7. The gateway processes the payment and redirects the user back to v4
 * 8. `App\Controller\GatewaysController` catches the redirection and calls the `handleRedirect` method of the respective interface
 * 9. `App\Controller\GatewaysController` also catches webhook events sent by the gateway and calls the `handleWebhook` method.
 */
interface GatewayInterface
{
    /**
     * @return string A short, unique, descriptive string for this Gateway
     */
    public static function getId(): string;

    /**
     * @return string the commercially-preferred displayable name of the Gateway platform
     */
    public static function getName(): string;

    /**
     * @return ChargeType[] The charge types that can be processed by this Gateway
     */
    public static function getSupportedChargeTypes(): array;

    /**
     * @return string[] The roles that are allowed to process Checkouts with this Gateway
     */
    public static function getAllowedRoles(): array;

    /**
     * Connects with the payment gateway and creates a checkout session so the gateway can process the payment.
     *
     * @param Checkout $checkout The Checkout with the data for the payment to be charged
     *
     * @return Checkout The Checkout updated with the data given by the gateway
     */
    public function process(Checkout $checkout): Checkout;

    /**
     * Connects with the payment gateway and issues a reimbursement.
     *
     * @param Charge $charge The Charge that must refunded
     *
     * @return Charge The Charge updated with the result of the refund operation
     */
    public function refund(Charge $charge): Charge;

    /**
     * When a user is redirected by the gateway we must handle the redirection
     * and then redirect the user back to a GUI.\
     * \
     * IMPORTANT: Redirection shouldn't be the only source of truth for gateway flow completion.
     * Webhook handling is the preferred method.\
     * This should, ideally, only handle redirections of the user back to the desired web app.
     *
     * @param Request $request The HTTP Request object
     *
     * @return Response A RedirectResponse object to where the user should be redirected
     */
    public function handleRedirect(Request $request): RedirectResponse;

    /**
     * When supported by the gateway, webhook events should be the preferred method to receive gateway flow updates.
     *
     * @param Request $request The HTTP Request object of the webhook event
     *
     * @return Response A Response object to send back to the gateway
     */
    public function handleWebhook(Request $request): Response;
}
