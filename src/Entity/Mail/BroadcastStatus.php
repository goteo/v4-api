<?php

namespace App\Entity\Mail;

enum BroadcastStatus: string
{
    /**
     * The Broadcast is a work-in-progress not to be sent.
     */
    case InDraft = 'in_draft';

    /**
     * The Broadcast should be sent.
     */
    case ToSending = 'to_sending';

    /**
     * The Broadcast is being sent.
     */
    case InSending = 'in_sending';

    /**
     * The Broadcast has been fully sent.
     */
    case Sent = 'sent';

    /**
     * All of the Dispatchs of the Broadcast were attempted, but some had errors.
     */
    case SentErrors = 'sent.errors';
}
