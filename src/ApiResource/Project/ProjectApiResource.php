<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\CategoryApiResource;
use App\ApiResource\LocalizedApiResourceTrait;
use App\ApiResource\Matchfunding\MatchCallSubmissionApiResource;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\ApiResource\User\UserApiResource;
use App\Dto\ProjectCreationDto;
use App\Dto\ProjectUpdationDto;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectCalendar;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\ProjectVideo;
use App\Entity\Territory;
use App\Library\Link;
use App\Mapping\Transformer\BudgetMapTransformer;
use App\State\ApiResourceStateProvider;
use App\State\Project\ProjectStateProcessor;
use App\State\Project\ProjectStateProvider;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Projects describe a User-owned, community-led event that is to be discovered, developed and funded by the community.
 */
#[API\ApiResource(
    shortName: 'Project',
    stateOptions: new Options(entityClass: Project::class),
    provider: ApiResourceStateProvider::class,
)]
#[API\GetCollection()]
#[API\Post(
    input: ProjectCreationDto::class,
    processor: ProjectStateProcessor::class,
    security: 'is_granted("ROLE_USER")',
)]
#[API\Get(
    provider: ProjectStateProvider::class,
    uriTemplate: '/projects/{idOrSlug}',
    uriVariables: [
        'idOrSlug' => new API\Link(
            description: 'Project identifier or slug',
        ),
    ]
)]
#[API\Patch(
    input: ProjectUpdationDto::class,
    processor: ProjectStateProcessor::class,
    security: 'is_granted("PROJECT_EDIT", previous_object)',
)]
#[API\Delete(security: 'is_granted("PROJECT_EDIT", previous_object)')]
class ProjectApiResource
{
    use LocalizedApiResourceTrait;
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * A unique, non white space, string identifier for this Project.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public string $slug;

    /**
     * The Accounting holding the funds raised by this Project.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $accounting;

    /**
     * The User who owns this Project.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public UserApiResource $owner;

    /**
     * Main headline for the Project.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank()]
    public string $title;

    /**
     * Secondary headline for the Project.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank()]
    public string $subtitle;

    /**
     * URL to an image resource to be displayed as header.
     */
    #[Assert\Url()]
    public string $cover;

    /**
     * On `minimum`, Project will campaign until the minimum deadline.\
     * On `optimum`, Project will campaing until the minimum deadline,
     * and then until the optimum deadline if it did raise the minimum.
     */
    #[API\ApiProperty(writable: false)]
    public ProjectDeadline $deadline;

    /**
     * Deadlines and important Project dates.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(filterClass: DateFilter::class, properties: ['calendar.release', 'calendar.minimum', 'calendar.optimum'])]
    public ProjectCalendar $calendar;

    /**
     * A list of the available Categories of this Project.
     *
     * @var CategoryApiResource[]
     */
    #[Assert\NotBlank()]
    #[API\ApiProperty(writableLink: false)]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public array $categories;

    /**
     * ISO 3166 data about the Project's territory of interest.
     */
    #[Assert\NotBlank()]
    #[Assert\Valid()]
    #[API\ApiFilter(
        filterClass: SearchFilter::class,
        strategy: 'exact',
        properties: ['territory.country', 'territory.subLvl1', 'territory.subLvl2']
    )]
    public Territory $territory;

    /**
     * Rich-text (markdown) introduction to the project.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'partial')]
    public string $descBrief;

    /**
     * Rich-text (markdown) description on the main features of the project.
     */
    public string $descAbout;

    /**
     * Rich-text (markdown) about why this project is important.
     */
    public string $descGoal;

    /**
     * Rich-text (markdown) about team and previous experience.
     */
    public string $descTeam;

    /**
     * Extracted embedding data from the Project's video.
     */
    #[API\ApiProperty(writable: false)]
    #[MapFrom(source: Project::class, property: 'video')]
    public ProjectVideo $video;

    /**
     * The status of a Project represents how far it is in it's life-cycle.
     */
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("PROJECT_EDIT", previous_object)')]
    public ProjectStatus $status = ProjectStatus::InDraft;

    /**
     * List of the ProjectRewards this Project offers.
     *
     * @var array<int, RewardApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $rewards;

    /**
     * A detailed breakdown of the budget for this Project, as described by the associated BudgetItems.
     */
    #[API\ApiProperty(writable: false)]
    #[MapFrom(source: Project::class, transformer: BudgetMapTransformer::class)]
    public Budget $budget;

    /**
     * A list of the BudgetItems composing the budget of this Project.
     *
     * @var array<int, BudgetItemApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $budgetItems;

    /**
     * A list of the ProjectCollaborations this Project has.
     *
     * @var array<int, CollaborationApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $collaborations;

    /**
     * A list of the ProjectUpdates this Project has.
     *
     * @var array<int, UpdateApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $updates;

    /**
     * @var array<int, MatchCallSubmissionApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $matchCallSubmissions;

    /**
     * A list of URLs related to the Project.\
     * e.g: social profiles, project website.
     *
     * @var Link[]
     */
    #[API\ApiProperty(writable: false)]
    #[MapTo(Project::class, transformer: [self::class, 'parseLinks'])]
    #[MapFrom(Project::class, transformer: [self::class, 'parseLinks'])]
    public array $links = [];

    public static function parseLinks(array $values)
    {
        return \array_map(fn($value) => Link::tryFrom($value), $values);
    }
}
