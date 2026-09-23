<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\Entity\Tipjar;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use App\Validator\NotExisting;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Unlike other Money recipients a Tipjar receives money with no further goal.\
 * \
 * Tips to the platform owners and other no-purpose money can target a Tipjar.
 */
#[API\ApiResource(
    shortName: 'Tipjar',
    stateOptions: new Options(entityClass: Tipjar::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class
)]
#[API\GetCollection()]
#[API\Post()]
#[API\Get()]
#[API\Delete()]
#[API\Patch()]
class TipjarApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $accounting;

    /**
     * Human readable, non white space, unique string.
     */
    #[Assert\NotBlank()]
    #[NotExisting(Tipjar::class, 'name')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $name;
}
