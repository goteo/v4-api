<?php

namespace App\Security\Voter;

use App\ApiResource\Accounting\AccountingApiResource;
use App\Entity\User\User;
use App\Repository\Accounting\AccountingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountingVoter extends Voter
{
    use UserOwnedVoterTrait;

    public const EDIT = 'ACCOUNTING_EDIT';
    public const VIEW = 'ACCOUNTING_VIEW';

    public function __construct(
        private Security $security,
        private AccountingRepository $accountingRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof AccountingApiResource;
    }

    /**
     * @param AccountingApiResource $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $accounting = $this->accountingRepository->find($subject->id);

        switch ($accounting->getOwnerClass()) {
            case User::class:
                return $this->voteOnUser($attribute, $accounting->getOwner(), $user);
            default:
                return $this->voteOn($attribute, $accounting, $user);
        }
    }

    private function voteOn(string $attribute, mixed $subject, ?UserInterface $user): bool
    {
        switch ($attribute) {
            case self::EDIT:
                return $this->security->isGranted('ROLE_ADMIN', $user)
                    || $this->isOwnerOf($subject, $user);
            default:
                return true;
        }
    }

    private function voteOnUser(string $attribute, User $owner, ?UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
            case self::VIEW:
                return $this->security->isGranted('ROLE_ADMIN', $user)
                    || $this->isOwnerOf($owner, $user);
        }

        return false;
    }
}
