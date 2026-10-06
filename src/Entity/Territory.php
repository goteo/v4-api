<?php

namespace App\Entity;

use App\Validator\CountrySubdivision;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Territory
{
    public const UNKNOWN_COUNTRY_CODE = 'ZZ';

    /**
     * ISO 3166-1 alpha-2 two-letter country code.\
     * e.g: ES (Spain).
     */
    #[Assert\NotBlank()]
    #[Assert\Country(alpha3: false)]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public readonly ?string $country;

    /**
     * ISO 3166-2 first level subdivision code.\
     * e.g: ES-AN (Andalucía, Spain).
     */
    #[CountrySubdivision()]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public readonly ?string $subLvl1;

    /**
     * ISO 3166-2 second level subdivision code.\
     * e.g: ES-GR (Granada, Andalucía, Spain).
     */
    #[CountrySubdivision()]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public readonly ?string $subLvl2;

    /**
     * Plain-text address.\
     * e.g: Forn de l’Olivera 22B, 07012 Palma, Illes Balears.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public readonly ?string $address;

    public function __construct(
        ?string $country = null,
        ?string $subLvl1 = null,
        ?string $subLvl2 = null,
        ?string $address = null,
    ) {
        $this->country = $country ?? self::UNKNOWN_COUNTRY_CODE;
        $this->subLvl1 = $subLvl1 ?? null;
        $this->subLvl2 = $subLvl2 ?? null;
        $this->address = $address ?? null;
    }

    public static function unknown(?string $address = null): Territory
    {
        return new Territory(
            country: self::UNKNOWN_COUNTRY_CODE,
            address: $address
        );
    }
}
