<?php

namespace App\Mapping\Transformer;

use App\Entity\Accounting\AccountingOwnerInterface;
use App\Entity\Matchfunding\MatchCall;
use App\Entity\Project\Support;
use App\Entity\User\User;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class SupportDisplayNameMapTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private UserDisplayNameMapTransformer $userDisplayNameMapTransformer,
    ) {}

    /**
     * @param Support $source
     */
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $origin = $source->getOrigin();
        if (!$origin) {
            throw new \Exception(\sprintf('The Support with ID %s does not have an origin', $source->getId()));
        }

        return match ($origin->getOwnerClass()) {
            User::class => $this->userDisplayNameMapTransformer->transform($value, $origin->getUser(), $context),
            MatchCall::class => $origin->getMatchCall()->getTitle(),
            default => $this->attemptDisplayName($origin->getOwner()),
        };
    }

    private function attemptDisplayName(AccountingOwnerInterface $owner): string
    {
        if (\method_exists($owner, 'getTitle')) {
            return $owner->getTitle();
        }

        if (\method_exists($owner, 'getName')) {
            return $owner->getName();
        }

        throw new \Exception(\sprintf("AccountingOwnerInterface '%s' does not provide a displayable name", $owner::class));
    }
}
