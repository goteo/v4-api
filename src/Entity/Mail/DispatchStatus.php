<?php

namespace App\Entity\Mail;

enum DispatchStatus: string
{
    /**
     * Pending pick-up.
     */
    case InPending = 'in_pending';

    /**
     * An error prevents the delivery.
     */
    case InError = 'in_error';

    /**
     * The mail was dispatched to the User.
     */
    case Dispatched = 'dispatched';
}
