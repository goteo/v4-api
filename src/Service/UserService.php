<?php

namespace App\Service;

use App\Repository\User\UserRepository;
use Platoniq\RandomNameCombiner\NameGenerator;

use function Symfony\Component\String\u;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private string $locale,
    ) {}

    /**
     * Generates a random, unused handle from two words in the app locale and 4 digits.
     *
     * @return string A handle-valid string, e.g. `leon_leal_0421`
     */
    public function generateHandle(): string
    {
        try {
            $generator = new NameGenerator($this->locale);
        } catch (\InvalidArgumentException) {
            $generator = new NameGenerator();
        }

        do {
            $handle = \sprintf(
                '%s_%04d',
                u($generator->getName())->ascii()->lower()->replaceMatches('/[^a-z]+/', '_')->truncate(25)->trimEnd('_'),
                \random_int(0, 9999)
            );
        } while ($this->userRepository->findOneBy(['handle' => $handle]));

        return $handle;
    }

    /**
     * Normalizes a string into a handle-valid string.
     *
     * @param string $value The input string to be normalized into a valid handle
     * @param int    $min   The shortest possible length of the output string
     * @param int    $max   The longest possible length of the output string
     *
     * @return string A handle-valid string
     *
     * @throws \Exception If the given value could not be normalized into a compliant handle string
     */
    public static function asHandle(string $value, int $min = 4, int $max = 32): string
    {
        $nval = \strtolower($value);

        // If email remove provider
        $nval = preg_replace('/@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', '', $nval);

        // Replace known subs
        $nval = \str_replace(['&', '@', 'ñ', '=', '≠'], ['and', 'at', 'nn', 'eq', 'nq'], $nval);

        // Only lowercase a-z, numbers, underscore and middle dots in user handles
        $nval = \preg_replace('/[^a-z0-9_.]|^\.|\.$/', '_', \strtolower($nval));

        $nval = \str_pad($nval, $min, \sprintf('_%s', \hash('md5', $value)));
        $nval = \substr($nval, 0, $max);

        if (strlen(str_replace('_', '', $nval)) < 1) {
            throw new \Exception(\sprintf('The string \'%s\' could not be safely normalized for a handle', $value));
        }

        return $nval;
    }

    /**
     * Tries to split first and last names from a single string of names.
     *
     * @param string $name The full names string
     *
     * @return array{0: string, 1: string} index 0 contains the guess for the first name(s), index 1 contains guess for last name(s)
     */
    public static function guessNames(string $name): array
    {
        $namePieces = \explode(' ', $name);
        $namePiecesCount = \count($namePieces);

        $firstName = $name;
        $lastName = '';

        if ($namePiecesCount === 2) {
            [$firstName, $lastName] = $namePieces;
        }

        if ($namePiecesCount === 3) {
            $firstName = $namePieces[0];
            $lastName = \join(' ', \array_slice($namePieces, 1));
        }

        if ($namePiecesCount > 3) {
            $firstName = \join(' ', \array_slice($namePieces, 0, 2));
            $lastName = \join(' ', \array_slice($namePieces, 2));
        }

        return [$firstName, $lastName];
    }
}
