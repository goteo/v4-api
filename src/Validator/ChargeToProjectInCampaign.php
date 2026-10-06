<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ChargeToProjectInCampaign extends Constraint
{
    public string $message = 'The targeted Project {{ project }} behind Accounting {{ target }} is not in a status where it can receive Charges.';
}
