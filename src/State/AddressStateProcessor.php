<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AddressApiResource;
use App\Dto\AddressCreationDto;
use App\Dto\AddressUpdationDto;
use App\Entity\Address;
use App\Mapping\AutoMapper;

class AddressStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private EntityStateProcessor $entityStateProcessor,
    ) {}

    /**
     * @param AddressCreationDto|AddressUpdationDto $data
     *
     * @return AddressApiResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $address = match ($data::class) {
            AddressCreationDto::class => $this->getAddressFromCreation($data),
            AddressUpdationDto::class => $this->getAddressFromUpdate($data, $context),
            default => throw new \InvalidArgumentException('Unsupported input for Address resource'),
        };

        $address = $this->entityStateProcessor->process($address, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        if ($address === null) {
            return null;
        }

        return $this->autoMapper->map($address, AddressApiResource::class);
    }

    private function getAddressFromCreation(AddressCreationDto $data): Address
    {
        /** @var Address */
        $address = $this->autoMapper->map($data, Address::class);

        return $address;
    }

    private function getAddressFromUpdate(AddressUpdationDto $data, array $context): Address
    {
        /** @var Address */
        $address = $this->autoMapper->map($context['previous_data'], Address::class);

        return $this->autoMapper->map($data, $address);
    }
}
