<?php

namespace App\State\Gateway;

use ApiPlatform\Metadata as API;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Gateway\GatewayApiResource;
use App\Gateway\GatewayInterface;
use App\Gateway\GatewayLocator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GatewayStateProvider implements ProviderInterface
{
    public function __construct(
        private GatewayLocator $gateways,
        private Security $security,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        switch ($operation::class) {
            case API\GetCollection::class:
                return $this->getGateways();
            case API\Get::class:
                return $this->getGateway($uriVariables['id']);
            default:
                return $this->getGateways();
        }
    }

    private function getGateways(): array
    {
        $gateways = [];
        foreach ($this->gateways->getAll() as $gateway) {
            foreach ($gateway::getAllowedRoles() as $role) {
                $isGranted = $this->security->isGranted($role, $this->security->getUser());

                if (!$isGranted) {
                    continue 2;
                }
            }

            $gateways[] = $this->toResource($gateway);
        }

        return $gateways;
    }

    private function getGateway(string $id): GatewayApiResource
    {
        try {
            $gateway = $this->gateways->get($id);

            foreach ($gateway::getAllowedRoles() as $role) {
                $isGranted = $this->security->isGranted($role, $this->security->getUser());

                if (!$isGranted) {
                    throw new \Exception('Unauthorized Gateway role');
                }
            }

            return $this->toResource($gateway);
        } catch (\Exception $e) {
            throw new NotFoundHttpException('Not Found');
        }
    }

    private function toResource(GatewayInterface $gateway): GatewayApiResource
    {
        $resource = new GatewayApiResource();
        $resource->id = $gateway::getId();
        $resource->name = $gateway::getName();
        $resource->supports = $gateway::getSupportedChargeTypes();

        return $resource;
    }
}
