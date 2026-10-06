<?php

namespace App\Gateway;

use App\Entity\Gateway\Checkout;
use App\Gateway\Exception\DuplicateGatewayException;
use App\Gateway\Exception\MissingGatewayException;

class GatewayLocator
{
    /** @var GatewayInterface[] */
    private array $gatewaysById = [];

    /** @var GatewayInterface[] */
    private array $gatewaysByClass = [];

    public function __construct(iterable $instanceof)
    {
        foreach (\iterator_to_array($instanceof) as $key => $gateway) {
            $this->gatewaysByClass[$gateway::class] = $gateway;
        }

        self::validateGatewayIDs($this->gatewaysByClass);

        foreach ($this->gatewaysByClass as $class => $gateway) {
            $this->gatewaysById[$gateway::getId()] = $gateway;
        }
    }

    /**
     * @return array<string, GatewayInterface>
     */
    public function getAll(): array
    {
        return $this->gatewaysById;
    }

    /**
     * @param string $id ID of the Gateway interface implementation
     *
     * @throws \Exception When the `$id` does not match to that of an implemented Gateway
     */
    public function get(string $id): GatewayInterface
    {
        if (!\array_key_exists($id, $this->gatewaysById)) {
            throw new MissingGatewayException($id);
        }

        return $this->gatewaysById[$id];
    }

    /**
     * @throws \Exception When the $checkout::gateway does not match to that of an implemented Gateway
     */
    public function getForCheckout(Checkout $checkout): GatewayInterface
    {
        return $this->get($checkout->getGatewayId());
    }

    /**
     * Ensures the gateway IDs are unique for each gateway.
     *
     * @param array $gatewayClasses Fully-qualified Gateway class names
     *
     * @throws \Exception If there are two different Gateway classes that return the same name string
     */
    private static function validateGatewayIDs(array $gatewayClasses): void
    {
        $gatewaysValidated = [];
        foreach ($gatewayClasses as $gatewayClass) {
            $gatewayId = $gatewayClass::getId();

            if (\array_key_exists($gatewayId, $gatewaysValidated)) {
                throw new DuplicateGatewayException(
                    $gatewayId,
                    $gatewayClass::class,
                    $gatewaysValidated[$gatewayId]::class
                );
            }

            $gatewaysValidated[$gatewayId] = $gatewayClass;
        }
    }
}
