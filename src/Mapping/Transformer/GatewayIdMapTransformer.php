<?php

namespace App\Mapping\Transformer;

use App\ApiResource\Gateway\GatewayApiResource;
use App\Gateway\GatewayLocator;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class GatewayIdMapTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private GatewayLocator $gatewayLocator,
    ) {}

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $gateway = $this->gatewayLocator->get($value);

        $resource = new GatewayApiResource();
        $resource->id = $gateway::getId();
        $resource->name = $gateway::getName();
        $resource->supports = $gateway::getSupportedChargeTypes();

        return $resource;
    }
}
