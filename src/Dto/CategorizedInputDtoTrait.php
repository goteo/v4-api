<?php

namespace App\Dto;

trait CategorizedInputDtoTrait
{
    public const array CATEGORIES_OPENAPI_CONTEXT = [
        'type' => 'array',
        'items' => [
            'type' => 'string',
            'format' => 'iri-reference',
            'example' => '/categories/cooperativism',
        ],
        'example' => [
            '/categories/open-source-software',
            '/categories/cooperativism',
        ],
    ];

    public const array THEMES_OPENAPI_CONTEXT = [
        'type' => 'array',
        'items' => [
            'type' => 'string',
            'format' => 'iri-reference',
            'example' => '/themes/music',
        ],
        'example' => [
            '/themes/music',
            '/themes/art',
        ],
    ];
}
