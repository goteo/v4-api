<?php

namespace App\Validator;

use App\ApiResource\Project\RewardClaimApiResource;
use App\Money\Money;
use App\Money\MoneyService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class EnoughRewardChargeValidator extends ConstraintValidator
{
    public function __construct(
        private MoneyService $money,
    ) {}

    /**
     * @param RewardClaimApiResource $value
     * @param EnoughRewardCharge     $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $charge = $value->charge->money;
        $reward = new Money($value->reward->money->amount, $value->reward->money->currency);

        if ($this->money->isMoreOrSame($charge, $reward)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation()
        ;
    }
}
