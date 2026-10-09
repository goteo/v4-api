<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\Entity\Project\ReviewArea;
use App\Entity\Project\ReviewAreaRisk;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;

/**
 * A ProjectReviewArea represents one specific topic of evaluation for ProjectReviews.\
 * \
 * Conversations, feedback and evolution of the ProjectReview must happen around specific areas of review.\
 * Each area holds an associated risk, which represents the trust the reviewer has for the reviewed Project's health in that area,
 * areas can only be updated by the assigned reviewer or an user with `ROLE_ADMIN`.
 */
#[API\ApiResource(
    shortName: 'ProjectReviewArea',
    stateOptions: new Options(entityClass: ReviewArea::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(security: 'is_granted("ROLE_ADMIN")')]
#[API\Get(security: 'is_granted("REVIEWAREA_VIEW", object)')]
#[API\Patch(security: 'is_granted("REVIEWAREA_EDIT", object)')]
#[API\Delete(security: 'is_granted("REVIEWAREA_EDIT", object)')]
class ReviewAreaApiResource
{
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public ReviewApiResource $review;

    public string $title;

    public string $summary;

    public ReviewAreaRisk $risk;

    /**
     * @var ReviewCommentApiResource[]
     */
    public array $comments;
}
