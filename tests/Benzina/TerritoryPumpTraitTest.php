<?php

namespace App\Tests\Benzina;

use App\Benzina\TerritoryPumpTrait;
use PHPUnit\Framework\TestCase;

class TerritoryPumpTraitTest extends TestCase
{
    /** @var TerritoryPumpTrait */
    private $pump;

    public function setUp(): void
    {
        $this->pump = $this->getMockForTrait(TerritoryPumpTrait::class);
    }

    public function testCapitalizesResult()
    {
        $cleanAddress = $this->pump->cleanLocation('Test Address');

        $this->assertEquals('TEST ADDRESS', $cleanAddress);
    }

    /**
     * @dataProvider provideInternetAddresses
     */
    public function testSkipsInternetAddresses($internetAddress)
    {
        $this->assertEquals('', $this->pump->cleanLocation($internetAddress));
    }

    public function provideInternetAddresses(): array
    {
        return [
            ['127.0.0.1'],
            ['http://www.ecologiaperumanu.com/mapaproyecto.php'],
            ['http://www.esbaluard.org/es/'],
            ['https://www.google.es/maps/@40.0320175,-5.7727571,500m/data=!3m1!1e3'],
            ['www.google.com/maps/place/Tecoanapa,+Gro./@16.9873264,-99.2593372,18.25z/data=!4m5!3m4!1s0x85ca2be1bd85bfcd:0xd3cb17e67573bf44!8m2!3d16.9865731!4d-99.2604936'],
        ];
    }

    /**
     * @dataProvider provideConjoinedAddresses
     */
    public function testStripsConjoinedAddresses($conjoinedAddress, $finalAddress)
    {
        $cleanAddress = $this->pump->cleanLocation($conjoinedAddress);

        $this->assertEquals($finalAddress, $cleanAddress);
    }

    public function provideConjoinedAddresses(): array
    {
        return [
            ['Jaca (Huesca) y www.lacasadelamontaña.com', 'JACA, HUESCA'],
            ['buenos aires, argentina y barcelona españa', 'BUENOS AIRES, ARGENTINA'],
            ['Barcelona y Bilbao', 'BARCELONA'],
            ['Madrid, España / San Francisco, EEUU', 'MADRID, ESPAÑA'],
            ['Calafou, Camí de Ca la Fou, s/n | CP: 08785  | Vallbona d´Anoia (Barcelona)', 'CALAFOU, CAMÍ DE CA LA FOU, S/N'],
        ];
    }

    /**
     * @dataProvider provideColonSpecifiedAddresses
     */
    public function testStripsColonSpecifiers($colonSpecified, $removedSpecifier)
    {
        $cleanAddress = $this->pump->cleanLocation($colonSpecified);

        $this->assertStringNotContainsString($removedSpecifier, $cleanAddress);
    }

    public function provideColonSpecifiedAddresses(): array
    {
        return [
            ['Obrador : Carrer Santander, 49 local 9, Barcelona. Venda al públic: Centre comercial Finestrelles, Esplugues de Llobregat', 'OBRADOR'],
            ["Lieu : Polytech'Nice-Sophia 930, Route des Colles, Sophia Antipolis", 'LIEU'],
            ['Universidad Carlos III de Madrid: Campus de Getafe, Calle Madrid, Getafe, España', 'Universidad Carlos III de Madrid'],
        ];
    }

    /**
     * @dataProvider provideBadPunctuations
     */
    public function testFixesBadPunctuations($badAddress, $fixedAddress)
    {
        $cleanAddress = $this->pump->cleanLocation($badAddress);

        $this->assertEquals($fixedAddress, $cleanAddress);
    }

    public function provideBadPunctuations(): array
    {
        return [
            [', Cali, Colombia', 'CALI, COLOMBIA'],
            ['Lobres (Granada', 'LOBRES, GRANADA'],
            ['California City, California, EE. UU.', 'CALIFORNIA CITY, CALIFORNIA, EE. UU'],
        ];
    }

    /**
     * @dataProvider provideCountryVariations
     */
    public function testStandarisesCountry($variation, $standard)
    {
        $cleanAddress = $this->pump->cleanLocation($variation);

        $this->assertEquals($standard, $cleanAddress);
    }

    public function provideCountryVariations(): array
    {
        return [
            ['SPAIN', 'ESPAÑA'],
            ['MADRID, ESPAÑA', 'MADRID, ESPAÑA'],
            ['MADRID, ESPANYA', 'MADRID, ESPAÑA'],
            ['MADRID, ESPAGNE', 'MADRID, ESPAÑA'],
        ];
    }
}
