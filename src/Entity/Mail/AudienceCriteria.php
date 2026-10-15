<?php

namespace App\Entity\Mail;

class AudienceCriteria
{
    public function __construct(
        public string $subject,
        public AudienceCriteriaOperator $operator,
        public string $referent,
    ) {}
}
