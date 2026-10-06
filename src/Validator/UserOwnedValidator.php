<?php

namespace App\Validator;

use ApiPlatform\Metadata\IriConverterInterface;
use App\ApiResource\User\UserApiResource;
use App\Mapping\AutoMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UserOwnedValidator extends ConstraintValidator
{
    public function __construct(
        private Security $security,
        private AutoMapper $mapper,
        private IriConverterInterface $iriConverter,
    ) {}

    /**
     * @param UserOwned $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = $this->iriConverter->getIriFromResource($value);
        $user = $this->security->getUser();

        if ($value && $user) {
            /** @var UserApiResource */
            $resource = $this->mapper->map($user, UserApiResource::class);
            $resourceIri = $this->iriConverter->getIriFromResource($resource);

            if ($value === $resourceIri) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation()
        ;
    }
}
