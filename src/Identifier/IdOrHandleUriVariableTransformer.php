<?php

namespace App\Identifier;

use ApiPlatform\Metadata\UriVariableTransformerInterface;

final class IdOrHandleUriVariableTransformer implements UriVariableTransformerInterface
{
    public function transform(mixed $value, array $types, array $context = []): int|string
    {
        if (
            is_int($value)
            || (\is_string($value) && \ctype_digit($value))
        ) {
            return (int) $value;
        }

        return (string) $value;
    }

    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        return isset($context['uri_variables_map']['idOrHandle']);
    }
}
