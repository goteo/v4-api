<?php

namespace App\Dto\User;

use ApiPlatform\Metadata as API;
use App\Entity\Territory;
use App\Entity\User\UserType;
use Symfony\Component\Validator\Constraints as Assert;

final class UserUpdationDto
{
    /**
     * A unique, non white space, byte-safe string identifier for this User.
     */
    #[Assert\Length(min: 4, max: 30)]
    #[Assert\Regex('/^[a-z0-9_]+$/')]
    public string $handle;

    /**
     * The User's given email address. Only available to themselves and platform administrators.
     */
    #[Assert\Email()]
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
}
