<?php

namespace App\State\Project;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Project\RewardApiResource;
use App\Entity\Project\Reward;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;

class RewardStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityStateProcessor $entityStateProcessor,
        private AutoMapper $autoMapper,
    ) {}

    /**
     * @param RewardApiResource $data
     *
     * @return RewardApiResource|null
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Reward */
        $reward = $this->autoMapper->map($data, Reward::class);
        $reward->setIsFinite($data->isFinite);

        if (!$reward->getId()) {
            $reward->setUnitsAvailable($reward->getUnitsTotal());
        }

        $reward = $this->entityStateProcessor->process($reward, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        return $this->autoMapper->map($reward, $data);
    }
}
