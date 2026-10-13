<?php

namespace App\Dto;

use ApiPlatform\Metadata as API;
use App\ApiResource\User\UserApiResource;
use Symfony\Component\Validator\Constraints as Assert;

class AddressCreationDto
{
    #[Assert\NotBlank()]
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("USER_EDIT", object.user)')]
    public UserApiResource $user;

    /**
     * First name(s) of the person receiving the shipment.
     */
    #[Assert\NotBlank()]
    public string $firstName;

    /**
     * Last name(s) of the person receiving the shipment.
     */
    #[Assert\NotBlank()]
    public string $lastName;

    /**
     * Line 1: usually street name and number.
     */
    #[Assert\NotBlank()]
    public string $line1;

    /**
     * Line 2: additional data like apartment number, door, etc.
     */
    public ?string $line2 = null;

    /**
     * Name of the city, or the lowest-available type of settlement to which the address lines belong.
     */
    #[Assert\NotBlank()]
    public string $city;

    /**
     * Postal/PIN/ZIP code to which the address lines belong.
     */
    #[Assert\NotBlank()]
    public string $postCode;

    /**
     * ISO 3166-1 alpha-2 two-letter country code.\
     * e.g: ES (Spain).
     */
    #[Assert\NotBlank()]
    #[Assert\Country()]
    public string $country;
}
