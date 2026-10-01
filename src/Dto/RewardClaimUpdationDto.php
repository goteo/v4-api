<?php

namespace App\Dto;

use ApiPlatform\Metadata as API;
use App\ApiResource\AddressApiResource;
use App\Entity\Project\RewardClaimStatus;

class RewardClaimUpdationDto
{
    /**
     * The point at which the claim over the reward is.\
     * May only be updated by admins or the User who owns the Project of the ProjectReward.
     */
    #[API\ApiProperty(security: 'is_granted("CLAIM_EDIT", object)')]
    public RewardClaimStatus $status;

    /**
     * If the reward is a physical object that needs to be delivered to an specific place.
     */
    #[API\ApiProperty(security: 'is_granted("CLAIM_OWNS", object)')]
    public ?AddressApiResource $address;
}
