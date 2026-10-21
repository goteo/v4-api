<?php

namespace App\Entity\User;

enum TaxIdType: string
{
    /**
     * National identity or tax identification document, for people and organizations.
     */
    case NationalId = 'national_id';

    case Passport = 'passport';

    /**
     * Any other national or foreign identification document.
     */
    case Other = 'other';
}
