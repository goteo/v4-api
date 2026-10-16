<?php

namespace App\Entity\User;

enum OrganizationLegalForm: string
{
    case Association = 'association';

    case Foundation = 'foundation';

    case Cooperative = 'cooperative';

    /**
     * For-profit companies of any kind.
     */
    case Company = 'company';

    /**
     * Self-employed professionals.
     */
    case SelfEmployed = 'self_employed';

    /**
     * Government bodies and public administrations.
     */
    case PublicAdministration = 'public_administration';

    case Other = 'other';
}
