<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\Theme;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use App\State\ThemeStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Theme, like in books, works as a "topic intent" grouping, for example:
 * friendship, good vs. evil, power and corruption, etc.\
 * \
 * Themes can only be modified by users with the role "ROLE_ADMIN", but can usually
 * be referenced by non-admin users in their own resources, such as Project owners.
 */
#[API\ApiResource(
    shortName: 'Theme',
    stateOptions: new Options(entityClass: Theme::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(security: 'is_granted("ROLE_ADMIN")')]
#[API\Get(
    provider: ThemeStateProvider::class,
    uriTemplate: '/themes/{idOrSlug}',
    uriVariables: [
        'idOrSlug' => new API\Link(
            description: 'Theme identifier or slug',
        ),
    ]
)]
#[API\Patch(security: 'is_granted("ROLE_ADMIN")')]
#[API\Delete(security: 'is_granted("ROLE_ADMIN")')]
class ThemeApiResource
{
    use LocalizedApiResourceTrait;

    /**
     * This value will identify this Theme in relationships with other resources.
     */
    #[API\ApiProperty(identifier: true)]
    public string $id;

    /**
     * A unique, non white space, string identifier for this Theme.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public string $slug;

    /**
     * A human-readable self-descriptive string of what this Theme is about.
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $name;
}
