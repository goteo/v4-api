<?php

namespace App\Service\Project;

use App\Entity\Project\Review;
use App\Entity\Project\ReviewArea;
use App\Entity\Project\ReviewType;

class ReviewService
{
    public function makeReview(ReviewType $type): Review
    {
        $review = new Review();
        $review->setType($type);

        /** @var ReviewArea[] */
        $areas = match ($type) {
            ReviewType::Campaign => $this->getCampaignReviewAreas(),
            ReviewType::Financial => $this->getFinancialReviewAreas(),
        };

        foreach ($areas as $area) {
            $review->addArea($area);
        }

        return $review;
    }

    /**
     * @return ReviewArea[]
     */
    private function getCampaignReviewAreas(): array
    {
        $config = new ReviewArea();
        $config->setTitle('configuration');

        $info = new ReviewArea();
        $info->setTitle('info');

        $rewards = new ReviewArea();
        $rewards->setTitle('rewards');

        $collabs = new ReviewArea();
        $collabs->setTitle('collaborations');

        $budget = new ReviewArea();
        $budget->setTitle('budget');

        $about = new ReviewArea();
        $about->setTitle('about');

        return [
            $config,
            $info,
            $rewards,
            $collabs,
            $budget,
            $about,
        ];
    }

    /**
     * @return ReviewArea[]
     *
     * @todo Add financial review areas
     */
    private function getFinancialReviewAreas(): array
    {
        return [];
    }
}
