<?php

namespace App\Entity\Mail;

enum BatchStatus: string
{
    /**
     * The Batch is not to be sent.
     */
    case InDraft = 'in_draft';

    /**
     * The Batch should be sent.
     */
    case ToSending = 'to_sending';

    /**
     * The Batch is being sent.
     */
    case InSending = 'in_sending';

    /**
     * The Batch has been fully sent.
     */
    case Sent = 'sent';
}
