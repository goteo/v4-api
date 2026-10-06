<?php

namespace App\State\Matchfunding;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Matchfunding\MatchCallSubmissionApiResource;
use App\Dto\Matchfunding\MatchCallSubmissionCreationDto;
use App\Entity\Matchfunding\MatchCallSubmission;
use App\Mapping\AutoMapper;
use App\State\EntityStateProcessor;

class MatchCallSubmissionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private EntityStateProcessor $entityProcessor,
    ) {}

    /**
     * @param MatchCallSubmissionApiResource|MatchCallSubmissionCreationDto $data
     *
     * @return MatchCallSubmissionApiResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var MatchCallSubmission */
        $submission = $this->autoMapper->map($data, MatchCallSubmission::class);
        $submission = $this->entityProcessor->process($submission, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        return $this->autoMapper->map($submission, MatchCallSubmissionApiResource::class);
    }
}
