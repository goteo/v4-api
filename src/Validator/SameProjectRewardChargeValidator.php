<?php

namespace App\Validator;

use App\ApiResource\Project\RewardClaimApiResource;
use App\Entity\Accounting\Accounting;
use App\Entity\Project\Project;
use App\Mapping\AutoMapper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SameProjectRewardChargeValidator extends ConstraintValidator
{
    public function __construct(
        private AutoMapper $mapper,
    ) {}

    /**
     * @param RewardClaimApiResource $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $reward = $value->reward;

        /** @var Accounting */
        $target = $this->mapper->map($value->charge->target, Accounting::class);

        if (
            $target->getProject() instanceof Project
            && $target->getProject()->getId() === $reward->project->id
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation()
        ;
    }
}
