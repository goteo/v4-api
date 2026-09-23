<?php

namespace App\ApiResource\User;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\Dto\User\UserSignupDto;
use App\Entity\Territory;
use App\Entity\User\User;
use App\Entity\User\UserType;
use App\Filter\EncryptedSearchFilter;
use App\Filter\InArrayFilter;
use App\Filter\OrderedLikeFilter;
use App\Filter\QFilter;
use App\Library\Link;
use App\Mapping\Transformer\UserDisplayNameMapTransformer;
use App\State\ApiResourceStateProvider;
use App\State\User\UserSignupProcessor;
use App\State\User\UserStateProcessor;
use App\State\User\UserStateProvider;
use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Users represent people who interact with the platform.
 */
#[API\ApiResource(
    shortName: 'User',
    stateOptions: new Options(entityClass: User::class),
    provider: ApiResourceStateProvider::class,
    processor: UserStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(input: UserSignupDto::class, processor: UserSignupProcessor::class)]
#[API\Get(
    provider: UserStateProvider::class,
    uriTemplate: '/users/{idOrHandle}',
    uriVariables: [
        'idOrHandle' => new API\Link(
            description: 'User identifier or handle'
        ),
    ]
)]
#[API\Patch(securityPostDenormalize: 'is_granted("USER_EDIT", previous_object)')]
#[API\Delete(securityPostDenormalize: 'is_granted("USER_EDIT", previous_object)')]
#[API\ApiFilter(QFilter::class, properties: [
    'q' => [
        'email',
        'person.firstName',
        'person.lastName',
        'organization.legalName',
        'organization.businessName',
    ],
])]
class UserApiResource
{
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(writable: false, identifier: true)]
    public int $id;

    /**
     * A unique, non white space, byte-safe string identifier for this User.
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 4, max: 30)]
    #[Assert\Regex('/^[a-z0-9_]+$/')]
    #[API\ApiFilter(filterClass: OrderedLikeFilter::class)]
    #[API\ApiFilter(OrderFilter::class, properties: ['handle'])]
    public string $handle;

    /**
     * The User's given email address. Only available to themselves and platform administrators.
     */
    #[Assert\NotBlank()]
    #[Assert\Email()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[API\ApiProperty(security: 'is_granted("USER_EDIT", object)')]
    public string $email;

    /**
     * Has this User confirmed their email address?
     */
    #[API\ApiProperty(writable: false, security: 'is_granted("USER_VIEW", object)')]
    public bool $emailConfirmed;

    /**
     * URL to the avatar image of this User.
     */
    #[Assert\Url()]
    public string $avatar;

    /**
     * Is this User for an individual acting on their own or a group of individuals?
     */
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("USER_EDIT", previous_object)')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public UserType $type;

    /**
     * A list of the roles assigned to this User. Admin scoped property.
     *
     * @var array<int, string>
     */
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("ROLE_ADMIN")')]
    #[API\ApiFilter(InArrayFilter::class, strategy: InArrayFilter::STRATEGY_AND)]
    public array $roles;

    #[API\ApiProperty(writable: false, security: 'is_granted("USER_VIEW", object)')]
    #[MapFrom(User::class, transformer: UserDisplayNameMapTransformer::class)]
    public string $displayName;

    /**
     * For `individual` User types: personal data about the User themselves.\
     * For `organization` User types: data for the organization representative or person managing the User.\
     * Only available to themselves and platform administrators.
     */
    #[API\ApiProperty(writable: false, security: 'is_granted("USER_EDIT", object)')]
    #[API\ApiFilter(EncryptedSearchFilter::class, properties: ['person.taxId'], strategy: 'is_granted("ROLE_ADMIN")')]
    public PersonApiResource $person;

    /**
     * For `organization` User types only. Legal entity data.
     */
    #[API\ApiProperty(writable: false, security: 'is_granted("USER_EDIT", object)')]
    #[API\ApiFilter(EncryptedSearchFilter::class, properties: ['organization.taxId'], strategy: 'is_granted("ROLE_ADMIN")')]
    public ?OrganizationApiResource $organization = null;

    /**
     * The Accounting for this User monetary movements.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public AccountingApiResource $accounting;

    /**
     * The Projects that are owned by this User.
     *
     * @var array<int, \App\ApiResource\Project\ProjectApiResource>
     */
    #[API\ApiProperty(writable: false)]
    public array $projects;

    /**
     * A flag determined by the platform for Users who are known to be active.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(BooleanFilter::class)]
    public bool $active;

    /**
     * A list of URLs provided by the User.\
     * e.g: social profiles, personal website.
     *
     * @var Link[]
     */
    #[API\ApiProperty(writable: false)]
    #[MapTo(User::class, transformer: [self::class, 'parseLinks'])]
    #[MapFrom(User::class, transformer: [self::class, 'parseLinks'])]
    public array $links = [];

    public static function parseLinks(array $values)
    {
        return \array_map(fn($value) => Link::tryFrom($value), $values);
    }

    /**
     * ISO 3166 data about the Users's location territory.
     */
    #[Assert\Valid()]
    #[API\ApiFilter(
        filterClass: SearchFilter::class,
        strategy: 'exact',
        properties: ['territory.country', 'territory.subLvl1', 'territory.subLvl2']
    )]
    public Territory $territory;

    /**
     * Free-form rich text description for the User.
     */
    public string $description;
}
