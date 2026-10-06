<?php

namespace App\Validator;

use App\ApiResource\Gateway\ChargeApiResource;
use App\Dto\Gateway\ChargeCreationDto;
use App\Entity\Accounting\Accounting;
use App\Entity\Project\ProjectStatus;
use App\Mapping\AutoMapper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ChargeToProjectInCampaignValidator extends ConstraintValidator
{
    public function __construct(
        private AutoMapper $mapper,
    ) {}

    /**
     * @param ChargeApiResource|ChargeCreationDto $value
     * @param ChargeToProjectInCampaign           $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        /** @var Accounting */
        $target = $this->mapper->map($value->target, Accounting::class);

        if ($target->getProject() === null) {
            return;
        }

        if ($target->getProject()->getStatus() === ProjectStatus::InCampaign) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ target }}', $value->target->id)
            ->setParameter('{{ project }}', $target->getProject()->getId())
            ->addViolation()
        ;
    }
}
