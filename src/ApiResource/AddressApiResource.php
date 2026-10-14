<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\User\UserApiResource;
use App\Dto\AddressCreationDto;
use App\Dto\AddressUpdationDto;
use App\Entity\Address;
use App\State\AddressStateProcessor;
use App\State\ApiResourceStateProvider;

/**
 * Addresses represent named locations that postal services can reach.
 */
#[API\ApiResource(
    shortName: 'Address',
    stateOptions: new Options(entityClass: Address::class),
    processor: AddressStateProcessor::class,
    provider: ApiResourceStateProvider::class,
    security: 'is_granted("ROLE_USER")'
)]
#[API\GetCollection()]
#[API\Post(
    input: AddressCreationDto::class,
    securityPostDenormalize: 'is_granted("USER_EDIT", object.user)'
)]
#[API\Get]
#[API\Patch(
    input: AddressUpdationDto::class,
    securityPostDenormalize: 'is_granted("ADDRESS_EDIT", previous_object)'
)]
#[API\Delete()]
class AddressApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public UserApiResource $user;

    /**
     * First name(s) of the person receiving the shipment.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    public string $firstName;

    /**
     * Last name(s) of the person receiving the shipment.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    public string $lastName;

    /**
     * Line 1: usually street name and number.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    public string $line1;

    /**
     * Line 2: additional data like apartment number, door, etc.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    public ?string $line2 = null;

    /**
     * Name of the city, or the lowest-available type of settlement to which the address lines belong.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $city;

    /**
     * Postal/PIN/ZIP code to which the address lines belong.
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $postCode;

    /**
     * ISO 3166-1 alpha-2 two-letter country code.\
     * e.g: ES (Spain).
     */
    #[API\ApiProperty(security: 'is_granted("ADDRESS_VIEW", object)')]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public string $country;
}
