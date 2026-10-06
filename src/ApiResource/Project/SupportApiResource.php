<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\Accounting\TransactionApiResource;
use App\ApiResource\MoneyOutput;
use App\Entity\Project\Support;
use App\Mapping\Transformer\SupportDisplayImageMapTransformer;
use App\Mapping\Transformer\SupportDisplayNameMapTransformer;
use App\Money\Totalization\TotalizedMoney;
use App\State\ApiResourceStateProvider;
use App\State\MoneyTotalStateProvider;
use App\State\Project\SupportStateProcessor;
use AutoMapper\Attribute\MapFrom;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProjectSupports gather Transactions going from one same origin to one same Project.\
 * \
 * MatchCalls specially might make several different Transactions to the same Project,
 * but their ProjectSupport remains the same just with updated money.
 */
#[API\ApiResource(
    shortName: 'ProjectSupport',
    stateOptions: new Options(entityClass: Support::class),
    provider: ApiResourceStateProvider::class,
)]
#[API\GetCollection()]
#[API\GetCollection(
    uriTemplate: '/project_supports/money_total',
    provider: MoneyTotalStateProvider::class,
    output: TotalizedMoney::class,
    paginationEnabled: false,
    openapi: new \ApiPlatform\OpenApi\Model\Operation(
        summary: 'Get money total',
        description: 'Returns a single TotalizedMoney object',
        responses: [
            '200' => [
                'description' => 'Totalized money',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/ProjectSupport.TotalizedMoney',
                        ],
                    ],
                ],
            ],
        ]
    )
)]
#[API\Get()]
#[API\Patch(
    security: 'is_granted("SUPPORT_EDIT", previous_object)',
    processor: SupportStateProcessor::class
)]
class SupportApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The Project being supported.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public ProjectApiResource $project;

    /**
     * The Accounting of origin for the Transactions under this ProjectSupport record.\
     * \
     * When `anonymous` is *true* the origin will only be public to admins and the User.
     */
    #[API\ApiProperty(writable: false, security: 'is_granted("SUPPORT_VIEW", object)')]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public ?AccountingApiResource $origin;

    #[API\ApiProperty(writable: false)]
    #[MapFrom(Support::class, transformer: SupportDisplayNameMapTransformer::class)]
    public ?string $displayName;

    #[API\ApiProperty(writable: false)]
    #[MapFrom(Support::class, transformer: SupportDisplayImageMapTransformer::class)]
    public ?string $displayImage;

    /**
     * The Transactions that were issued to the Project by the origin.
     *
     * @var array<int, TransactionApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $transactions;

    /**
     * The total monetary value of the Transactions going to the Project.
     */
    #[API\ApiProperty(writable: false)]
    public MoneyOutput $money;

    /**
     * If the origin wishes to remain anonymous behind this ProjectSupport.
     */
    #[Assert\NotNull()]
    #[Assert\Type('bool')]
    #[API\ApiFilter(BooleanFilter::class)]
    public bool $anonymous = true;

    /**
     * If this ProjectSupport comes from a MatchCall this flag will be true.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(BooleanFilter::class)]
    public bool $matchfunding;

    /**
     * A message of support from the User to the Project.
     */
    public ?string $message = null;
}
