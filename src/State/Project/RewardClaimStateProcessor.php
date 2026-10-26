<?php

namespace App\State\Project;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\ApiResource\Project\RewardClaimApiResource;
use App\Dto\RewardClaimCreationDto;
use App\Dto\RewardClaimUpdationDto;
use App\Entity\Address;
use App\Entity\Project\RewardClaim;
use App\Entity\User\User;
use App\Mapping\AutoMapper;
use App\Repository\Project\RewardClaimRepository;
use App\Security\Voter\AccountingVoter;
use App\State\EntityStateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class RewardClaimStateProcessor implements ProcessorInterface
{
    private const ADDRESS_NOT_OWNED_MESSAGE = 'The Address does not belong to the User who owns the claim.';

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
            RewardClaimUpdationDto::class => $this->getClaimFromUpdate($data, $context),
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

    /**
     * An Address can only be used in a claim by the User who owns that claim.
     */
    public static function isAddressOwnedBy(?Address $address, ?User $owner): bool
    {
        return $address === null || $address->getUser() === $owner;
    }

    private function getClaimFromUpdate(RewardClaimUpdationDto $data, array $context): RewardClaim
    {
        $claim = $this->rewardClaimRepository->find($context['previous_data']->id);
        if (!$claim) {
            throw new NotFoundHttpException();
        }

        if (isset($data->address)) {
            /** @var Address */
            $address = $this->autoMapper->map($data->address, Address::class);

            $this->validateAddressOwner($address, $claim->getOwner(), $data);
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

        $this->validateAddressOwner($claim->getAddress(), $owner, $data);

        $claim->setOwner($owner);

        return $claim;
    }

    private function validateAddressOwner(?Address $address, ?User $owner, object $data): void
    {
        if (self::isAddressOwnedBy($address, $owner)) {
            return;
        }

        throw new ValidationException(new ConstraintViolationList([
            new ConstraintViolation(
                self::ADDRESS_NOT_OWNED_MESSAGE,
                self::ADDRESS_NOT_OWNED_MESSAGE,
                [],
                $data,
                'address',
                $data->address
            ),
        ]));
    }
}
