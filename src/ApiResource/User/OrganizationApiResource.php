<?php

namespace App\ApiResource\User;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\Entity\User\Organization;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sometimes an Organization is behind a User account,
 * we keep their data separated from the User record to allow for other User types,
 * like the ones owned by an individual, to exist all at the same level.\
 * \
 * All Organization records are only visible to their respective owners and platform admins.
 * Sensitive data is encrypted before being stored in the database.
 */
#[API\ApiResource(
    shortName: 'Organization',
    security: 'is_granted("ORGANIZATION_VIEW", object)',
    stateOptions: new Options(entityClass: Organization::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
    uriTemplate: '/users/{id}/organization',
    uriVariables: [
        'id' => new API\Link(
            fromClass: UserApiResource::class,
            fromProperty: 'organization',
            description: 'User identifier'
        ),
    ]
)]
#[API\Get()]
#[API\Patch(
    securityPostDenormalize: 'is_granted("ORGANIZATION_EDIT", previous_object)',
)]
class OrganizationApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public UserApiResource $user;

    /**
     * Organization ID for tax purposes. e.g: NIF (formerly CIF), Umsatzsteuer-Id, EID, etc.
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("ORGANIZATION_EDIT", previous_object)',
    )]
    public string $taxId;

    /**
     * Organization legal name before government,
     * as it appears on legal documents issued by or for this organization.\
     * Will be used as last option for the display name of the User.
     */
    #[Assert\NotBlank()]
    #[Assert\Length(min: 1)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("ORGANIZATION_EDIT", previous_object)',
    )]
    public string $legalName;

    /**
     * The name under which the organization presents itself.\
     * Might be similar to the legal name or completely different.\
     * Will be used as display name for the User when present.
     */
    #[Assert\Length(min: 1)]
    #[API\ApiProperty(
        securityPostDenormalize: 'is_granted("ORGANIZATION_EDIT", previous_object)',
    )]
    public string $businessName;
}
