<?php

namespace App\State\Project;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Project\RewardClaimApiResource;
use App\Dto\RewardClaimCreationDto;
use App\Dto\RewardClaimUpdationDto;
use App\Entity\Project\RewardClaim;
use App\Mapping\AutoMapper;
use App\Repository\Project\RewardClaimRepository;
use App\Security\Voter\AccountingVoter;
use App\State\EntityStateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RewardClaimStateProcessor implements ProcessorInterface
{
    public function __construct(
        private AutoMapper $autoMapper,
        private RewardClaimRepository $rewardClaimRepository,
        private Security $security,
        private EntityStateProcessor $entityStateProcessor,
    ) {}

    /**
     * @param RewardClaimCreationDto|RewardClaimUpdationDto $data
     *
     * @return RewardClaimApiResource|null
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $claim = match ($data::class) {
            RewardClaimUpdationDto::class => $this->getClaimFromUpdate($context),
            RewardClaimCreationDto::class => $this->getClaimFromCreate($data),
        };

        $claim = $this->entityStateProcessor->process($claim, $operation, $uriVariables, $context);

        if ($operation instanceof DeleteOperationInterface) {
            return;
        }

        if ($claim === null) {
            return null;
        }

        return $this->autoMapper->map($claim, $data);
    }

    private function getClaimFromUpdate(array $context): RewardClaim
    {
        $claim = $this->rewardClaimRepository->find($context['previous_data']->id);
        if (!$claim) {
            throw new NotFoundHttpException();
        }

        return $claim;
    }

    private function getClaimFromCreate(RewardClaimCreationDto $data): RewardClaim
    {
        /** @var RewardClaim */
        $claim = $this->autoMapper->map($data, RewardClaim::class);

        $origin = $claim->getCharge()->getCheckout()->getOrigin();
        if (!$this->security->isGranted(AccountingVoter::EDIT, $origin)) {
            throw new AuthenticationException();
        }

        $owner = $origin->getUser();

        $claim->setOwner($owner);

        return $claim;
    }
}
