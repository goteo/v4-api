<?php

namespace App\Entity\Project;

enum ReviewAreaRisk: string
{
    /**
     * The risk acceptable or none. Green light.
     */
    case Low = 'low';

    /**
     * The risk is moderate or somewhat worrying. Yellow light.
     */
    case Mid = 'mid';

    /**
     * The risk is severe and/or worrying. Red light.
     */
    case High = 'high';
}
