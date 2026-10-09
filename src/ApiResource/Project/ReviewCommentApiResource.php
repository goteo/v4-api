<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\ApiResource\User\UserApiResource;
use App\Entity\Project\ReviewComment;
use App\State\ApiResourceStateProcessor;
use App\State\ApiResourceStateProvider;
use App\Validator\UserOwned;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProjectReviewComments hold the conversation between the reviewer and the reviewed Project owner.
 */
#[API\ApiResource(
    shortName: 'ProjectReviewComment',
    stateOptions: new Options(entityClass: ReviewComment::class),
    provider: ApiResourceStateProvider::class,
    processor: ApiResourceStateProcessor::class,
)]
#[API\GetCollection()]
#[API\Post(securityPostDenormalize: 'is_granted("REVIEWAREA_VIEW", object.area)')]
#[API\Get(security: 'is_granted("REVIEWAREA_VIEW", object.area)')]
#[API\Delete(security: 'is_granted("REVIEWAREA_VIEW", object.area)')]
class ReviewCommentApiResource
{
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public ReviewAreaApiResource $area;

    #[UserOwned()]
    #[Assert\NotBlank()]
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public UserApiResource $author;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 15)]
    public string $body;
}
