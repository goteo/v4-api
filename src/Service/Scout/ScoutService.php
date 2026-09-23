<?php

namespace App\Service\Scout;

use Embed\Embed;
use Embed\Http\Crawler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class ScoutService
{
    private Embed $embed;

    /**
     * @param iterable<ScoutProcessorInterface> $processors
     */
    public function __construct(
        private iterable $processors,
        private ClientInterface $httpClient,
    ) {
        $embed = new Embed(new Crawler($httpClient));

        $this->embed = $embed;
    }

    private function normalizeUrl(string $url): string
    {
        $nurl = $url;

        if (!\parse_url($url, \PHP_URL_SCHEME)) {
            $nurl = \sprintf('https://%s', $url);
        }

        return $nurl;
    }

    /**
     * @param string $url A URL to an external resource
     *
     * @throws InvalidUriException      When the given $url string could not be validated as an actual URL
     * @throws NonCrawlableUriException If the given $url string points to a file
     */
    public function get(string $url): ScoutResult
    {
        $url = $this->normalizeUrl($url);

        $host = \parse_url($url, \PHP_URL_HOST);
        $isValidUrl = Validation::createIsValidCallable(null, new Url(), new NotBlank());

        if (!\str_contains($host ?? '', '.') || !$isValidUrl($url)) {
            throw new InvalidUriException();
        }

        $uri = $this->embed->getCrawler()->createUri($url);
        $response = $this->httpClient->sendRequest(
            new \GuzzleHttp\Psr7\Request('HEAD', $url)
        );

        if (!$this->isCrawlable($response)) {
            throw new NonCrawlableUriException($uri);
        }

        $info = $this->embed->get($url);

        $result = new ScoutResult(
            $info->getUri(),
            $info->getRequest(),
            $info->getResponse(),
            $info->getCrawler()
        );

        foreach ($this->processors as $processor) {
            if (!$processor->supports($result)) {
                continue;
            }

            $result = $processor->process($result);

            if (isset($result->retry) && $result->retry !== null) {
                return $this->get($result->retry);
            }
        }

        return $result;
    }

    private function isCrawlable(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if ($contentType === '') {
            return true;
        }

        $contentType = \strtolower(\trim(\explode(';', $contentType)[0]));

        return \in_array($contentType, [
            'text/html',
            'application/xhtml+xml',
        ], true);
    }
}
