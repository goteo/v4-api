<?php

namespace App\Entity\Project;

enum CollaborationCandidacyStatus: string
{
    /**
     * Candidacy is pending review by a Project manager.
     */
    case ToReview = 'to_review';

    /**
     * Candidacy is under review by a Project manager.
     */
    case InReview = 'in_review';

    /**
     * Candidacy was rejected by a Project manager. Final.
     */
    case Rejected = 'rejected';

    /**
     * Candidacy was approved by a Project manager. Final.
     */
    case Approved = 'approved';
}
