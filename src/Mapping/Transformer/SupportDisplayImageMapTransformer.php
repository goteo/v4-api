<?php

namespace App\Mapping\Transformer;

use App\Entity\Accounting\AccountingOwnerInterface;
use App\Entity\Project\Support;
use App\Entity\User\User;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class SupportDisplayImageMapTransformer implements PropertyTransformerInterface
{
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
            User::class => $origin->getUser()->getAvatar(),
            default => $this->attemptDisplayImage($origin->getOwner()),
        };
    }

    private function attemptDisplayImage(AccountingOwnerInterface $owner): string
    {
        if (\method_exists($owner, 'getImage')) {
            return $owner->getImage();
        }

        if (\method_exists($owner, 'getIcon')) {
            return $owner->getIcon();
        }

        throw new \Exception(\sprintf("AccountingOwnerInterface '%s' does not provide a displayable image", $owner::class));
    }
}
