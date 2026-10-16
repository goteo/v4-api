<?php

namespace App\Entity\User;

enum TaxIdType: string
{
    /**
     * Spanish tax identification number, for people (formerly DNI) and organizations (formerly CIF).
     */
    case Nif = 'nif';

    /**
     * Spanish foreigner identity number.
     */
    case Nie = 'nie';

    case Passport = 'passport';

    /**
     * Any other national or foreign identification document.
     */
    case Other = 'other';
}
