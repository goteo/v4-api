<?php

namespace App\Dto\User;

use App\Entity\User\User;
use App\Entity\User\UserType;
use App\Validator\NotExisting;
use Symfony\Component\Validator\Constraints as Assert;

final class UserSignupDto
{
    /**
     * A valid e-mail address for the new User.
     */
    #[Assert\NotBlank()]
    #[Assert\Email()]
    #[NotExisting(User::class, 'email')]
    public string $email;

    /**
     * The authentication password for the new User. Plaintext string,
     * will be hashed by the API.
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 8)]
    public string $password;

    /**
     * Is this User for an individual acting on their own or a group of individuals?
     */
    #[Assert\NotBlank()]
    public UserType $type = UserType::Individual;
}
