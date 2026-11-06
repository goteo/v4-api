<?php

namespace App\Mapping\Transformer;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Library\Link;
use App\Service\Scout\InvalidUriException;
use App\Service\Scout\NonCrawlableUriException;
use App\Service\Scout\ScoutResult;
use App\Service\Scout\ScoutService;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class RawLinksMapTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private ScoutService $scoutService,
    ) {}

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $links = [];
        foreach ($value as $i => $rawLink) {
            $link = new Link();
            // Scouting follows redirects (e.g. Facebook to its login page), keep the URL as given
            $link->url = $this->scoutService->normalizeUrl($rawLink);
            $link->rel = null;

            try {
                $link->rel = $this->getRel($this->scoutService->get($rawLink));
            } catch (NonCrawlableUriException) {
            } catch (InvalidUriException|ClientExceptionInterface) {
                throw new ValidationException(new ConstraintViolationList([
                    new ConstraintViolation('Invalid or unreachable URL', null, [], null, \sprintf('links[%d]', $i), $rawLink),
                ]));
            }

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
        if (!$anchor instanceof \DOMElement) {
            return null;
        }

        return $anchor->getAttribute('rel');
    }
}
