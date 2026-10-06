<?php

namespace App\Mapping;

use AutoMapper\AutoMapper as InnerMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\MapperContext;

class AutoMapper implements AutoMapperInterface
{
    public const CACHE_DIR = 'automapper';

    public const DEFAULT_CONTEXT = [
        MapperContext::DEPTH => 1,
        MapperContext::SKIP_NULL_VALUES => true,
        // MapperContext::SKIP_UNINITIALIZED_VALUES => true,
    ];

    private AutoMapperInterface $innerMapper;

    public function __construct(
        ?string $cacheDirectory = null,
        iterable $mapProviders = [],
        iterable $mapTransformers = [],
    ) {
        $this->innerMapper = InnerMapper::create(
            cacheDirectory: \sprintf('%s%s%s', $cacheDirectory, \DIRECTORY_SEPARATOR, self::CACHE_DIR),
            providers: $mapProviders,
            propertyTransformers: $mapTransformers,
        );
    }

    public function map(array|object $source, string|array|object $target, array $context = []): array|object|null
    {
        return $this->innerMapper->map($source, $target, [
            ...self::DEFAULT_CONTEXT,
            ...$context,
        ]);
    }

    public function mapCollection(iterable $collection, string $target, array $context = []): array
    {
        $items = [];

        foreach ($collection as $element) {
            $items[] = $this->innerMapper->map($element, $target, [
                ...self::DEFAULT_CONTEXT,
                ...$context,
            ]);
        }

        return $items;
    }
}
