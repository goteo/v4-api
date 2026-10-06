<?php

namespace App\Dto;

trait CategoryInputDtoTrait
{
    public const array CATEGORIES_OPENAPI_CONTEXT = [
        'type' => 'array',
        'items' => [
            'type' => 'string',
            'format' => 'iri-reference',
            'example' => '/categories/music',
        ],
        'example' => [
            '/categories/music',
            '/categories/art',
        ],
    ];
}
