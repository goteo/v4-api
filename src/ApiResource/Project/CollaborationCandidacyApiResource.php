<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\ApiResource\User\UserApiResource;
use App\Entity\Project\CollaborationCandidacy;
use App\Entity\Project\CollaborationCandidacyStatus;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use App\Validator as AssertCustom;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProjectCollaborationsCandidacies represent one User's willingness to fill in for a ProjectCollaboration.
 */
#[API\ApiResource(
    shortName: 'ProjectCollaborationCandidacy',
    stateOptions: new Options(entityClass: CollaborationCandidacy::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
    securityPostDenormalize: 'is_granted("ROLE_USER")',
)]
class CollaborationCandidacyApiResource
{
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The ProjectCollaboration to which this candidacy is applying to.
     */
    #[Assert\NotBlank()]
    #[AssertCustom\CollaborationIsOpen()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public CollaborationApiResource $collaboration;

    /**
     * The User applying to fill for the ProjectCollaboration.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("USER_EDIT", object.user)')]
    public UserApiResource $user;

    /**
     * Information about this candidacy.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'partial')]
    public string $description;

    /**
     * Life-cycle status of the candidacy.
     */
    #[Assert\NotNull()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[API\ApiProperty(securityPostDenormalize: 'is_granted("PROJECT_EDIT", object.collaboration.project)')]
    public CollaborationCandidacyStatus $status = CollaborationCandidacyStatus::ToReview;
}
