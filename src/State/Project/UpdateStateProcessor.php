<?php

namespace App\State\Project;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Project\UpdateApiResource;
use App\Entity\Project\Update;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UpdateStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityStateProcessor $entityStateProcessor,
        private AutoMapper $autoMapper,
        private Security $security,
    ) {}

    /**
     * @param UpdateApiResource $data
     *
     * @return UpdateApiResource|null
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Update */
        $update = $this->autoMapper->map($data, Update::class);

        $user = $this->security->getUser();

        if (!$user) {
            throw new AuthenticationException();
        }

        if (!$update->getId()) {
            $update->setAuthor($user);
        }

        $update = $this->entityStateProcessor->process($update, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        return $this->autoMapper->map($update, $data);
    }
}
