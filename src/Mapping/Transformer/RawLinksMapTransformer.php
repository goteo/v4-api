<?php

namespace App\Mapping\Transformer;

use App\Library\Link;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class RawLinksMapTransformer implements PropertyTransformerInterface
{
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $links = [];
        foreach ($value as $rawLink) {
            // Links are not crawled: sites behind a login wall redirect crawlers to their login page
            $link = new Link();
            $link->url = $rawLink;
            $link->rel = 'external';

            $links[] = $link;
        }

        return $links;
    }
}
