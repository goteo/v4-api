<?php

namespace App\Service\Project;

use App\Entity\Territory;
use App\Service\Nominatim\NominatimService;

class TerritoryService
{
    public function __construct(
        private NominatimService $nominatimService,
    ) {}

    public function search(string $query): Territory
    {
        $search = $this->nominatimService->search($query);

        if (empty($search)) {
            return Territory::unknown($query);
        }

        $result = $search[0];

        if (!\array_key_exists('address', $result)) {
            throw new \Exception("Key 'address' not found in Nominatim result. Did you forget to pass `addressDetails = true`?");
        }

        if (!\array_key_exists('country_code', $result['address'])) {
            return Territory::unknown($query);
        }

        return $this->processResult($result);
    }

    private function processResult(array $result): Territory
    {
        $address = $result['address'];
        $country = \strtoupper($address['country_code']);

        $subLvl1 = null;
        $subLvl2 = null;

        foreach ($address as $addrKey => $addrVal) {
            if (!\str_starts_with($addrKey, 'ISO3166-2')) {
                continue;
            }

            $level = \array_reverse(\explode('-', $addrKey))[0];
            $level = \intval(\str_replace('lvl', '', $level));

            if ($level < 5) {
                $subLvl1 = $addrVal;
            } else {
                $subLvl2 = $addrVal;
            }
        }

        return new Territory(
            $country,
            $subLvl1,
            $subLvl2,
            $result['display_name']
        );
    }
}
