<?php

namespace App\Entity\Mail;

enum BroadcastStatus: string
{
    /**
     * The Broadcast is a work-in-progress not to be processed.
     */
    case InDraft = 'in_draft';

    /**
     * Waiting for Dispatch generation.
     */
    case ToProcess = 'to_process';

    /**
     * Dispatch records are being generated.
     */
    case InProcess = 'in_process';

    /**
     * All the Dispatch records have been generated.
     */
    case Processed = 'processed';
}
