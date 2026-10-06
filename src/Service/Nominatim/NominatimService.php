<?php

namespace App\Service\Nominatim;

use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NominatimService
{
    public const NOMINATIM_BASE_URI = 'https://nominatim.openstreetmap.org';

    private float $lastRequestTime = 0.0;

    /**
     * Cache calls to Nominatim for 24 hours.
     * This ensures freshness of the data but minimizes bulk-operations hit rates to Nominatim.
     */
    public const NOMINATIM_CACHE_TTL = 86400;

    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
        $this->httpClient = $httpClient->withOptions([
            'base_uri' => self::NOMINATIM_BASE_URI,
        ]);
    }

    /**
     * Main request method with built-in caching.
     *
     * @param string              $endpoint One of the available nominatim endpoints
     * @param array{query: array} $options  List of parameters to be passed to the endpoint
     *
     * @see https://nominatim.org/release-docs/develop/api/Overview/
     */
    private function request(
        string $endpoint,
        array $options,
    ): array {
        $cacheKey = \sprintf('%s?%s', \ltrim($endpoint, '/'), \http_build_query($options['query']));

        return $this->cache->get(
            $cacheKey,
            function (CacheItemInterface $item) use ($endpoint, $options) {
                $item->expiresAfter(self::NOMINATIM_CACHE_TTL);

                $this->throttle();
                $response = $this->httpClient->request('GET', $endpoint, $options);

                return \json_decode($response->getContent(), true);
            }
        );
    }

    /**
     * Get data from the `/search` endpoint.
     *
     * @see https://nominatim.org/release-docs/develop/api/Search/
     */
    public function search(
        string $query,
        int $limit = 1,
        bool $addressDetails = true,
        bool $extraTags = false,
        bool $nameDetails = false,
    ): array {
        return $this->request('/search', [
            'query' => [
                'q' => $query,
                'limit' => $limit,
                'addressdetails' => (int) $addressDetails,
                'extratags' => (int) $extraTags,
                'namedetails' => (int) $nameDetails,
                'format' => OutputFormat::Json->value,
            ],
        ]);
    }

    private function throttle(): void
    {
        $minInterval = 1.1;

        $now = microtime(true);
        $delta = $now - $this->lastRequestTime;

        if ($delta < $minInterval) {
            usleep((int) (($minInterval - $delta) * 1000000));
        }

        $this->lastRequestTime = microtime(true);
    }
}
