<?php

namespace App\ApiResource\User;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\User\Person;
use App\Entity\User\PersonGender;
use App\Entity\User\TaxIdType;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Most times a real person is behind a User account,
 * we keep their data separated from the User record to allow for other User types,
 * like the ones owned by an organization, to exist all at the same level.\
 * \
 * All Person records are only visible to their respective owners and platform admins.
 * Sensitive personal data is encrypted before being stored in the database.
 */
#[API\ApiResource(
    shortName: 'Person',
    security: 'is_granted("PERSON_VIEW", object)',
    stateOptions: new Options(entityClass: Person::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
    uriTemplate: '/users/{id}/person',
    uriVariables: [
        'id' => new API\Link(
            fromClass: UserApiResource::class,
            fromProperty: 'person',
            description: 'User identifier'
        ),
    ]
)]
#[API\Get()]
#[API\Patch(
    securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
)]
class PersonApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public UserApiResource $user;

    /**
     * Personal ID for tax purposes. e.g: NIF, Steuer-ID, SSN, ITIN, etc.
     */
    #[Assert\Length(min: 2)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public string $taxId;

    /**
     * First-part of the name of the person,
     * in most western conventions this is the given name(s). e.g: John, Juan, etc.
     */
    #[Assert\Length(min: 1)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public string $firstName;

    /**
     * Last-part of the name of the person,
     * in most western conventions this is the family name(s). e.g: Smith, Herrera, etc.
     */
    #[Assert\Length(min: 1)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public string $lastName;

    /**
     * The kind of document the tax ID belongs to.
     */
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public ?TaxIdType $taxIdType = null;

    /**
     * Year of birth of the person. e.g: 1990.
     */
    #[Assert\GreaterThanOrEqual(1900)]
    #[Assert\Callback([self::class, 'validateBirthYear'])]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public ?int $birthYear = null;

    /**
     * Gender of the person.
     */
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("PERSON_EDIT", previous_object)',
    )]
    public ?PersonGender $gender = null;

    /**
     * `Range` only resolves relative time strings (e.g. 'now') against DateTime values, not int years.
     */
    public static function validateBirthYear(?int $birthYear, ExecutionContextInterface $context): void
    {
        if ($birthYear !== null && $birthYear > (int) date('Y')) {
            $context->buildViolation('The birth year cannot be in the future.')->addViolation();
        }
    }
}
