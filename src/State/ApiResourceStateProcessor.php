<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Mapping\AutoMapper;

class ApiResourceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private EntityStateProcessor $entityStateProcessor,
    ) {}

    /**
     * @return T2
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $entity = $this->asEntity($data, $operation->getStateOptions());
        $entity = $this->entityStateProcessor->process($entity, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        if ($entity === null) {
            return null;
        }

        return $this->autoMapper->map($entity, $data);
    }

    public function asEntity(mixed $data, Options $options): object
    {
        /** @var object */
        $entity = $this->autoMapper->map($data, $options->getEntityClass());

        return $entity;
    }
}
