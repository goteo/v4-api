<?php

namespace App\Entity\Mail;

class AudienceCriteria
{
    public function __construct(
        public string $subject,
        public AudienceCriteriaOperator $operator,
        public string $referent,
    ) {}

    public static function fromArray(array $array): self
    {
        $operator = $array['operator'] instanceof AudienceCriteriaOperator
            ? $array['operator']
            : AudienceCriteriaOperator::tryFrom($array['operator']);

        return new self(
            $array['subject'],
            $operator,
            $array['referent']
        );
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
