<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class NotExisting extends Constraint
{
    public string $message = 'The {{ property }} \'{{ value }}\' is already in use for {{ entity }} records.';

    public function __construct(
        /**
         * FQCN of the entity to check uniqueness for.
         */
        public string $entity,
        /**
         * Name of the property in the `entity` for which to check uniqueness.
         */
        public string $property,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
