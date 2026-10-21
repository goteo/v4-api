<?php

namespace App\Dto\User;

use ApiPlatform\Metadata as API;
use App\ApiResource\CategoryApiResource;
use App\ApiResource\ThemeApiResource;
use App\ApiResource\User\UserApiResource;
use App\Dto\CategorizedInputDtoTrait;
use App\Entity\Territory;
use App\Entity\User\User;
use App\Entity\User\UserReferralSource;
use App\Entity\User\UserType;
use App\Mapping\Transformer\RawLinksMapTransformer;
use App\Validator\NotExisting;
use App\Validator\UserHandle;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

final class UserUpdationDto
{
    use CategorizedInputDtoTrait;

    /**
     * A unique, non white space, byte-safe string identifier for this User.
     */
    #[UserHandle]
    #[NotExisting(User::class, 'handle')]
    public string $handle;

    /**
     * The User's given email address. Only available to themselves and platform administrators.
     */
    #[Assert\Email()]
    #[NotExisting(User::class, 'email')]
    public string $email;

    /**
     * The authentication password for the User. Plaintext string,
     * will be hashed by the API.
     */
    #[Assert\Length(min: 8)]
    public string $password;

    /**
     * URL to the avatar image of this User.
     */
    #[Assert\Url()]
    public string $avatar;

    /**
     * Is this User for an individual acting on their own or a group of individuals?
     */
    public UserType $type;

    /**
     * A list of the roles assigned to this User. Admin scoped property.
     *
     * @var array<int, string>
     */
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("ROLE_ADMIN")')]
    public array $roles;

    /**
     * A list of absolute URLs.\
     * e.g: social profiles, personal website.
     *
     * @var array<int, string>
     */
    #[MapTo(User::class, transformer: RawLinksMapTransformer::class)]
    #[MapTo(UserApiResource::class, transformer: RawLinksMapTransformer::class)]
    public array $links = [];

    /**
     * ISO 3166 data about the Users's location territory.
     */
    #[Assert\Valid()]
    public Territory $territory;

    /**
     * Free-form rich text description for the User.
     */
    public string $description;

    /**
     * Has the User agreed to share their location with the platform?
     * Used to generate anonymous statistics and impact indicators, never disclosed to third parties.
     */
    public bool $shareLocation;

    /**
     * Does this User wish to have their personal data to remain undisclosed to 3rd parties?
     */
    public bool $isAnonymous;

    /**
     * How the User got to know about the platform.
     */
    public ?UserReferralSource $referralSource;

    /**
     * The Categories the User is interested in.
     *
     * @var CategoryApiResource[]
     */
    #[API\ApiProperty(writableLink: false, openapiContext: self::CATEGORIES_OPENAPI_CONTEXT)]
    public array $preferredCategories;

    /**
     * The Themes the User is interested in.
     *
     * @var ThemeApiResource[]
     */
    #[API\ApiProperty(writableLink: false, openapiContext: self::THEMES_OPENAPI_CONTEXT)]
    public array $preferredThemes;
}
