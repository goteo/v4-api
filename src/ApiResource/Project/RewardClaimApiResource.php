<?php

namespace App\ApiResource\Project;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata as API;
use App\ApiResource\AddressApiResource;
use App\ApiResource\Gateway\ChargeApiResource;
use App\ApiResource\User\UserApiResource;
use App\Dto\RewardClaimCreationDto;
use App\Dto\RewardClaimUpdationDto;
use App\Entity\Project\RewardClaim;
use App\Entity\Project\RewardClaimStatus;
use App\State\ApiResourceStateProvider;
use App\State\Project\RewardClaimStateProcessor;

/**
 * A ProjectRewardClaim represents the will of an User who wishes to obtain one ProjectReward.\
 * \
 * Once created ProjectRewardClaims can only be deleted by the User who owns it,
 * while their status can only be updated by the User who owns the Project of the claimed ProjectReward.
 */
#[API\ApiResource(
    shortName: 'ProjectRewardClaim',
    stateOptions: new Options(entityClass: RewardClaim::class),
    provider: ApiResourceStateProvider::class,
    processor: RewardClaimStateProcessor::class
)]
#[API\GetCollection()]
#[API\Post(input: RewardClaimCreationDto::class)]
#[API\Get()]
#[API\Patch(input: RewardClaimUpdationDto::class)]
#[API\Delete(security: 'is_granted("CLAIM_OWNS", object)')]
class RewardClaimApiResource
{
    #[API\ApiProperty(identifier: true, writable: false)]
    public int $id;

    /**
     * The User claiming the ProjectReward. Derived from the GatewayCharge.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public UserApiResource $owner;

    /**
     * The GatewayCharge granting access to the ProjectReward.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public ChargeApiResource $charge;

    /**
     * The ProjectReward being claimed.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public RewardApiResource $reward;

    /**
     * The point at which the claim is in its life-cylce.
     */
    #[API\ApiFilter(SearchFilter::class, strategy: 'exact')]
    public RewardClaimStatus $status;

    /**
     * Only used when the reward is a physical object that needs to be shipped.
     */
    #[API\ApiProperty(readableLink: true)]
    #[API\ApiFilter(SearchFilter::class, properties: ['address.city' => 'partial'])]
    #[API\ApiFilter(SearchFilter::class, properties: ['address.postCode' => 'partial'])]
    #[API\ApiFilter(SearchFilter::class, properties: ['address.country' => 'partial'])]
    public ?AddressApiResource $address;
}
