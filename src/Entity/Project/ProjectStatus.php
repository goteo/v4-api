<?php

namespace App\Entity\Project;

/**
 * Projects have a start and an end, and in the meantime they go through different phases represented under this status.
 */
enum ProjectStatus: string
{
    /**
     * Project has just been created.
     */
    case InDraft = 'in_draft';

    /**
     * Owner finished editing and Project is ready for campaign review.
     */
    case ToCampaignReview = 'to_campaign_review';

    /**
     * A reviewer is reviewing the Project for campaign.
     */
    case InCampaignReview = 'in_campaign_review';

    /**
     * Owner requested to do changes by the campaign reviewer.
     */
    case InCampaignReviewToChange = 'in_campaign_review.to_change';

    /**
     * Owner finished with requested changes and is ready for review again.
     */
    case InCampaignReviewToReview = 'in_campaign_review.to_review';

    /**
     * Project is KO for campaign by the reviewer, final.
     */
    case CampaignReviewRejected = 'campaign_review.rejected';

    /**
     * Project is OK for campaign by the reviewer, can move forward.
     */
    case ToCampaign = 'to_campaign';

    /**
     * Project is in live campaign and raising funds.
     */
    case InCampaign = 'in_campaign';

    /**
     * Project failed to raise enough funds, final.
     */
    case CampaignFailed = 'campaign.failed';

    /**
     * Project was pulled out of campaign, final.
     */
    case CampaignCancelled = 'campaign.cancelled';

    /**
     * Project raised enough funds, can move forward.
     */
    case ToFundingReview = 'to_funding_review';

    /**
     * Project is under financial review.
     */
    case InFundingReview = 'in_funding_review';

    /**
     * Owner requested to do changes by the financial reviewer.
     */
    case InFundingReviewToChange = 'in_funding_review.to_change';

    /**
     * Owner finished with requested changes and is ready for financial review again.
     */
    case InFundingReviewToReview = 'in_funding_review.to_review';

    /**
     * Project is KO for funding by the reviewer, final.
     */
    case FundinReviewRejected = 'funding_review.rejected';

    /**
     * Project is OK for funding by the reviewer, can move forward.
     */
    case ToFunding = 'to_funding';

    /**
     * Project is being funded.
     */
    case InFunding = 'in_funding';

    /**
     * Project's funds were paid. Final.
     */
    case FundingPaid = 'funding.paid';
}
