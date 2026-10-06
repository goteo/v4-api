<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\Category;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use App\State\CategoryStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A Category can be used by other resources as a "topic intent".\
 * For example. Projects might relate with up to 2 Categories, which are used by the Project
 * as a way to describe itself and can be used to discover similar Projects.\
 * \
 * Categories can only be modified by users with the role "ROLE_ADMIN", but can usually
 * be referenced by non-admin users in their own resources, such as Project owners.
 */
#[API\ApiResource(
    shortName: 'Category',
    stateOptions: new Options(entityClass: Category::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(security: 'is_granted("ROLE_ADMIN")')]
#[API\Get(
    provider: CategoryStateProvider::class,
    uriTemplate: '/categories/{idOrSlug}',
    uriVariables: [
        'idOrSlug' => new API\Link(
            description: 'Category identifier or slug',
        ),
    ]
)]
#[API\Patch(security: 'is_granted("ROLE_ADMIN")')]
#[API\Delete(security: 'is_granted("ROLE_ADMIN")')]
class CategoryApiResource
{
    use LocalizedApiResourceTrait;

    /**
     * This value will identify this Category in relationships with other resources.
     */
    #[API\ApiProperty(identifier: true)]
    public string $id;

    /**
     * A unique, non white space, string identifier for this Category.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public string $slug;

    /**
     * A human-readable self-descriptive string of what this Category is about.
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $name;
}
