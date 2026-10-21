<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AddressUpdationDto
{
    /**
     * First name(s) of the person receiving the shipment.
     */
    #[Assert\Length(min: 1)]
    public ?string $firstName = null;

    /**
     * Last name(s) of the person receiving the shipment.
     */
    #[Assert\Length(min: 1)]
    public ?string $lastName = null;

    /**
     * Line 1: usually street name and number.
     */
    #[Assert\Length(min: 1)]
    public ?string $line1 = null;

    /**
     * Line 2: additional data like apartment number, door, etc.
     */
    #[Assert\Length(min: 1)]
    public ?string $line2 = null;

    /**
     * Name of the city, or the lowest-available type of settlement to which the address lines belong.
     */
    #[Assert\Length(min: 1)]
    public ?string $city = null;

    /**
     * Postal/PIN/ZIP code to which the address lines belong.
     */
    #[Assert\Length(min: 3)]
    public ?string $postCode = null;

    /**
     * ISO 3166-1 alpha-2 two-letter country code.\
     * e.g: ES (Spain).
     */
    #[Assert\Country()]
    public ?string $country = null;
}
