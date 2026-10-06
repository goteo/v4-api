<?php

namespace App\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Project\SupportApiResource;
use App\Entity\Project\Support;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SupportStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityStateProcessor $entityStateProcessor,
        private Security $security,
        private AutoMapper $autoMapper,
    ) {}

    /**
     * @param SupportApiResource $data
     *
     * @return SupportApiResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new AuthenticationException();
        }

        $support = $this->autoMapper->map($data, Support::class);

        $support = $this->entityStateProcessor->process($support, $operation, $uriVariables, $context);

        return $this->autoMapper->map($support, SupportApiResource::class);
    }
}
