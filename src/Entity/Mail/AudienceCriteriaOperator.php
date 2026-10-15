<?php

namespace App\Entity\Mail;

enum AudienceCriteriaOperator: string
{
    case Equals = 'eq';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
}
