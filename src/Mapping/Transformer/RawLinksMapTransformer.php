<?php

namespace App\Mapping\Transformer;

use App\Library\Link;
use App\Service\Scout\ScoutResult;
use App\Service\Scout\ScoutService;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class RawLinksMapTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private ScoutService $scoutService,
    ) {}

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $links = [];
        foreach ($value as $rawLink) {
            $info = $this->scoutService->get($rawLink);

            $link = new Link();
            $link->url = $info->getUri();
            $link->rel = $this->getRel($info);

            $links[] = $link;
        }

        return $links;
    }

    private function getRel(ScoutResult $info): ?string
    {
        $nodes = (new \DOMXPath($info->getDocument()->getDocument()))->query('//a[@rel]');

        if ($nodes->length === 0) {
            return null;
        }

        $anchor = $nodes->item(0);

        /**
         * @disregard P103 Undefined method 'getAttribute'.
         */
        return $anchor->getAttribute('rel');
    }
}
