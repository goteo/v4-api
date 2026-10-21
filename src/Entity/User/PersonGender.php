<?php

namespace App\Entity\User;

enum PersonGender: string
{
    case Female = 'female';

    case Male = 'male';

    case NonBinary = 'non_binary';

    /**
     * Any other gender identity, or the person prefers not to say.
     */
    case Other = 'other';
}
