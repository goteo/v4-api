<?php

namespace App\Money\Totalization;

class TotalizerLocator
{
    /** @var array<string, TotalizerInterface> */
    private array $totalizers = [];

    public function __construct(
        iterable $interfaces,
    ) {
        /** @var TotalizerInterface[] */
        $totalizers = \iterator_to_array($interfaces);

        foreach ($totalizers as $totalizer) {
            $resource = $totalizer::getSupportedResource();

            if (\array_key_exists($resource, $this->totalizers)) {
                throw new \Exception(\sprintf(
                    "Duplicated TotalizerInterface. The resource '%s' is already supported by %s",
                    $resource,
                    $totalizers[$resource]::class
                ));
            }

            $this->totalizers[$resource] = $totalizer;
        }
    }

    public function get(string $resource): ?TotalizerInterface
    {
        return $this->totalizers[$resource] ?? null;
    }

    /**
     * @return array<string, TotalizerInterface>
     */
    public function getAll(): array
    {
        return $this->totalizers;
    }
}
