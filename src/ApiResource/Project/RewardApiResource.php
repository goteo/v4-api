<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\LocalizedApiResourceTrait;
use App\ApiResource\MoneyInput;
use App\ApiResource\TimestampedCreationApiResource;
use App\ApiResource\TimestampedUpdationApiResource;
use App\Entity\Project\Reward;
use App\State\ApiResourceStateProvider;
use App\State\Project\RewardStateProcessor;
use AutoMapper\Attribute\MapTo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A ProjectReward is something the Project owner wishes to give in exchange for contributions to their Project.
 */
#[API\ApiResource(
    shortName: 'ProjectReward',
    stateOptions: new Options(entityClass: Reward::class),
    provider: ApiResourceStateProvider::class,
    processor: RewardStateProcessor::class,
    securityPostDenormalize: 'is_granted("PROJECT_EDIT", object.project)',
    securityPostDenormalizeMessage: 'You do not have permission to add Rewards to that Project'
)]
class RewardApiResource
{
    use LocalizedApiResourceTrait;
    use TimestampedCreationApiResource;
    use TimestampedUpdationApiResource;

    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The project which gives this reward.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(filterClass: SearchFilter::class, strategy: 'exact')]
    public ProjectApiResource $project;

    /**
     * A short, descriptive title for this reward.
     */
    #[Assert\NotBlank()]
    public string $title;

    /**
     * Information about this reward. More detailed than the title.
     */
    #[Assert\NotBlank()]
    #[MapTo(if: 'source.description != null')]
    public ?string $description = null;

    /**
     * URL to an image resource to be displayed as header.
     */
    #[Assert\Url()]
    public string $cover;

    /**
     * The minimal monetary sum to be able to claim this reward.
     */
    #[Assert\NotBlank()]
    #[API\ApiFilter(OrderFilter::class, properties: ['money.amount'])]
    public MoneyInput $money;

    /**
     * Rewards might be finite, i.e: has a limited amount of existing unitsTotal.
     */
    #[Assert\NotNull()]
    #[Assert\Type('bool')]
    #[API\ApiFilter(BooleanFilter::class)]
    public bool $isFinite = false;

    /**
     * For finite rewards, the total amount of existing units.\
     * Required if `isFinite`.
     */
    #[Assert\When('this.isFinite == true', [new Assert\Positive()])]
    public ?int $unitsTotal = null;

    /**
     * The total amount of claims on this Reward.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(OrderFilter::class)]
    public int $unitsClaimed = 0;

    /**
     * For finite rewards, the currently available amount of units that can be claimed.
     */
    #[API\ApiProperty(writable: false)]
    #[API\ApiFilter(OrderFilter::class)]
    public int $unitsAvailable = 0;
}
