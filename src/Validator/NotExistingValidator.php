<?php

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class NotExistingValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param NotExisting $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $entityClass = $constraint->entity;
        $repository = $this->entityManager->getRepository($entityClass);

        if (
            $value === null
            || $value === ''
            || $repository->findOneBy([$constraint->property => $value]) === null
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ property }}', $constraint->property)
            ->setParameter('{{ value }}', $value)
            ->setParameter('{{ entity }}', $this->getClassShortName($entityClass))
            ->addViolation()
        ;
    }

    private function getClassShortName(string $class): string
    {
        $reflect = new \ReflectionClass($class);

        return $reflect->getShortName();
    }
}
