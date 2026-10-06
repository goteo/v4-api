<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class CollaborationIsOpen extends Constraint
{
    public string $message = 'The Collaboration {{ collaboration }} is not open for new Candidacies.';
}
