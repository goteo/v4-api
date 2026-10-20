<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Validates a string as a User handle.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class UserHandle extends Compound
{
    public const MIN_LENGTH = 4;
    public const MAX_LENGTH = 30;

    /**
     * Characters allowed in a User handle, as a regex character class body.
     */
    public const CHARACTERS = 'a-z0-9_';

    public const PATTERN = '/^['.self::CHARACTERS.']+$/';

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Length(min: self::MIN_LENGTH, max: self::MAX_LENGTH),
            new Assert\Regex(self::PATTERN),
        ];
    }
}
