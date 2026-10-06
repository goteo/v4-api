<?php

namespace App\Entity\Project;

enum RewardClaimStatus: string
{
    /**
     * The claim has not been yet processed by the reward provider.
     */
    case InPending = 'in_pending';

    /**
     * The claim has been fulfilled and the user can be considered as rewarded.
     */
    case Fulfilled = 'fulfilled';
}
