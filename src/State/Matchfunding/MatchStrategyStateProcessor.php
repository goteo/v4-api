<?php

namespace App\State\Matchfunding;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Matchfunding\MatchStrategyApiResource;
use App\Entity\Matchfunding\MatchStrategy;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;
use Doctrine\ORM\EntityManagerInterface;

class MatchStrategyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private EntityStateProcessor $entityProcessor,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param MatchStrategyApiResource $data
     *
     * @return MatchStrategyApiResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var MatchStrategy */
        $strategy = $this->autoMapper->map($data, MatchStrategy::class);

        $call = $strategy->getCall();

        if ($operation instanceof DeleteOperationInterface) {
            $call->removeStrategy($strategy);
        } else {
            $call->addStrategy($strategy);
        }

        $this->entityManager->persist($call);
        $this->entityManager->flush();

        $strategy = $this->entityProcessor->process($strategy, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        if ($strategy === null) {
            return null;
        }

        return $this->autoMapper->map($strategy, MatchStrategyApiResource::class);
    }
}
