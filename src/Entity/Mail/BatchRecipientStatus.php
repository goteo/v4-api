<?php

namespace App\Entity\Mail;

enum BatchRecipientStatus: string
{
    /**
     * The reception is pending.
     */
    case InPending = 'in_pending';

    /**
     * An error prevents reaching the recipient.
     */
    case InError = 'in_error';

    /**
     * The recipient was reached.
     */
    case Reached = 'reached';
}
