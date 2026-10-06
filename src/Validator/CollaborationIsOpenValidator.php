<?php

namespace App\Validator;

use App\ApiResource\Project\CollaborationApiResource;
use App\Entity\Project\Collaboration;
use App\Entity\Project\ProjectStatus;
use App\Mapping\AutoMapper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class CollaborationIsOpenValidator extends ConstraintValidator
{
    public function __construct(
        private AutoMapper $mapper,
    ) {}

    /**
     * @param CollaborationApiResource $value
     * @param CollaborationIsOpen      $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        /** @var Collaboration */
        $collaboration = $this->mapper->map($value, Collaboration::class);

        if (
            $collaboration->isFulfilled() === false
            && $collaboration->getProject()->getStatus() === ProjectStatus::InCampaign
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ collaboration }}', $collaboration->getId())
            ->addViolation()
        ;
    }
}
