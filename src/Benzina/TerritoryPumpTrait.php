<?php

namespace App\Benzina;

trait TerritoryPumpTrait
{
    /**
     * Normalizes flexible address strings to obtain highly cacheable and improved search queries.
     * Based on analysis of the Goteo v3 `project.project_location` values.
     *
     * @param int $detailLevel Desired number of remaining components in output address
     */
    public static function cleanLocation(string $location, int $detailLevel = 3): string
    {
        // Skip web addresses
        if (
            \str_starts_with($location, 'www.')
            || \str_starts_with($location, 'http://')
            || \str_starts_with($location, 'https://')
        ) {
            return '';
        }

        // Remove secondary conjoined places from locations
        // e.g: "España y el mundo" -> "España"
        foreach ([' / ', ' | ', ' - ', ' y ', ' i ', ' and ', ' & '] as $conjoinment) {
            if (\str_contains($location, $conjoinment)) {
                $location = \explode($conjoinment, $location)[0];
            }
        }

        // Normalize parenthesis
        if (\str_contains($location, '(') || \str_contains($location, ')')) {
            $location = \str_replace('(', ',', $location);
            $location = \str_replace(')', '', $location);
        }

        // Remove colon specifications
        // e.g: "Universidad Carlos III de Madrid: Campus de Getafe, Calle Madrid, Getafe, España" -> "Campus de Getafe, Calle Madrid, Getafe, España"
        $location = \preg_replace('/^[\w ]+:/', '', $location);

        // Process comma-separated address pieces
        $location = \explode(',', $location);

        // Clean non desired location pieces
        $location = \array_filter($location, function ($l) {
            if (empty($l)) {
                return false;
            }

            // Skip numeric only pieces: coordinates, street numbers, etc
            if (\preg_match('/^[-\d.]*$/', $l) || \str_contains($l, 'º')) {
                return false;
            }

            return true;
        });

        // Normalize location pieces
        $location = \array_map(function ($l) {
            // Trim spaces and numbers at start of piece
            $l = \preg_replace('/^[\d ]+/', '', \trim($l));

            // Normalize typos and name variations
            foreach (self::COMMON_VARIATIONS as $standard => $variations) {
                if (in_array(\mb_strtoupper($l), $variations)) {
                    $l = $standard;
                }
            }

            return $l;
        }, $location);

        $location = \join(', ', \array_slice($location, -1 * $detailLevel));

        // Trim remaining numbers and punctuation marks
        $location = \preg_replace('/^[\d\.\,\-;]+/', '', $location);
        $location = \preg_replace('/[\d\.\,\-;]+$/', '', $location);

        return \mb_strtoupper(\trim($location));
    }

    /**
     * @var array<string, array> The standard preferred name and a list of possible variations and misspellings
     */
    private const COMMON_VARIATIONS = [
        'PERÚ' => ['PÉROU'],
        'ECUADOR' => ['EQUADOR'],
        'MÉXICO' => ['MEXICO', 'MESSICO', 'MX'],
        'CIUDAD DE MÉXICO' => ['CDMX'],
        'EUROPA' => ['EUROPEAN UNION', 'EUROPE'],
        'FRANCIA' => ['FRANCE'],
        'ITALIA' => ['ITALY'],
        'ALEMANIA' => ['GERMANY', 'DEUTSCHLAND'],
        'FINLANDIA' => ['FINLAND'],
        'SUECIA' => ['SWEDEN', 'SVERIGE'],
        'ESPAÑA' => ['ESPANYA', 'ESPANHA', 'ESPAGNE', 'SPAGNA', 'SPANIEN', 'SPAIN', 'ESTADO ESPAÑOL', 'ESPAINIA'],
        'ANDALUCÍA' => ['ANDALUCIA', 'ANDALUSIA'],
        'CÁDIZ' => ['CADIZ'],
        'CÓRDOBA' => ['CORDOBA'],
        'COMUNIDAD VALENCIANA' => ['COMUNITAT VALENCIANA', 'PAÍS VALENCIÀ'],
        'VALENCIA' => ['VALÈNCIA', 'PROVINCIA DE VALENCIA'],
        'ALICANTE' => ['ALACANT'],
        'CASTELLÓN DE LA PLANA' => ['CASTELLÓ DE LA PLANA', 'CASTELLON DE LA PLANA'],
        'LAS PALMAS DE GRAN CANARIA' => ['LAS PALMAS'],
        'CÁCERES' => ['CACERES'],
        'MÉRIDA' => ['MERIDA'],
        'LEÓN' => ['LEON'],
        'LEGANÉS' => ['LEGANES'],
        'BALEARES' => ['BALEAREN', 'BALEARS', 'ISLAS BALEARES', 'ILLES BALEARS', 'BALEARIC ISLANDS'],
        'GALICIA' => ['GALIZA'],
        'A CORUÑA' => ['LA CORUÑA', 'CORUÑA'],
        'OURENSE' => ['ORENSE'],
        'CATALUÑA' => ['CATALUNYA', 'PAÏSOS CATALANS', 'CATALUNA', 'CATALONIA'],
        'LLEIDA' => ['LÉRIDA', 'LERIDA'],
        'TARRASA' => ['TERRASA'],
        'BARCELONA' => ['PROVINCIA DE BARCELONA', 'BARCELONE', 'A BARCELONA'],
        'HOSPITALET DE LLOBREGAT' => ["L'HOSPITALET DE LLOBREGAT"],
        'SANT ADRIÀ DE BESÒS' => ['SANT ADRIÀ DEL BESÒS'],
        'MURCIA' => ['REGIÓN DE MURCIA'],
        'PAÍS VASCO' => ['EUSKADI', 'EUSKAL HERRIA'],
        'BILBAO' => ['BILBO'],
        'SAN SEBASTIÁN' => ['DONOSTIA', 'DONOSTIA-SAN SEBASTIÁN'],
        'VIZCAYA' => ['BIZKAIA'],
        'IRÚN' => ['IRUN'],
        'NAVARRA' => ['NAVARRE', 'NAFARROA'],
        'PAMPLONA' => ['IRUÑEA'],
        'CANARIAS' => ['ISLAS CANARIAS', 'CANARY ISLANDS'],
    ];
}
