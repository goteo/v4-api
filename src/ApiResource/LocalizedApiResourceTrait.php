<?php

namespace App\ApiResource;

use ApiPlatform\Metadata as API;
use AutoMapper\Attribute\MapFrom;

trait LocalizedApiResourceTrait
{
    /**
     * List of the available content locales.
     *
     * @var array<string>
     */
    #[API\ApiProperty(writable: false)]
    #[MapFrom(property: 'locales')]
    public array $locales = [];

    public function setLocales(array $locales): void
    {
        $this->locales = $locales;
    }
}
