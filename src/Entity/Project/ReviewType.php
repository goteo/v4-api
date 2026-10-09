<?php

namespace App\Entity\Project;

enum ReviewType: string
{
    /**
     * The goal is to determine if a Project is fit for campaigning or not.
     */
    case Campaign = 'campaign';

    /**
     * The goal is to determine if a Project is fit for payment or not.
     */
    case Financial = 'financial';
}
