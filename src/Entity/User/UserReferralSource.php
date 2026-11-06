<?php

namespace App\Entity\User;

enum UserReferralSource: string
{
    case SearchEngine = 'search_engine';

    case SocialMedia = 'social_media';

    /**
     * Word of mouth: a friend, relative or acquaintance.
     */
    case Friend = 'friend';

    /**
     * News, press or media coverage.
     */
    case Press = 'press';

    /**
     * An event, talk or workshop.
     */
    case Event = 'event';

    case Other = 'other';
}
