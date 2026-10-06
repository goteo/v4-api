<?php

namespace App\Entity;

use Gedmo\Translatable\Translatable;

/**
 * Provides an advanced way to work with Translatable entities.
 *
 * {@inheritdoc}
 *
 * @see https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/translatable.md#translatable-behavior-extension-for-doctrine
 */
interface LocalizedInterface extends Translatable
{
    public function getId(): int|string|null;

    /**
     * Set the locale of the working content for translations.
     */
    public function setTranslatableLocale(string $locale);

    /**
     * Obtain a list of the available locales to query translations of the entity.
     */
    public function getLocales(): array;

    /**
     * Add to the list of available translation locales.
     */
    public function addLocale(string $locale): static;

    /**
     * Remove from the list of available translation locales.
     */
    public function removeLocale(string $locale): static;

    /**
     * Set the list of available translation locales.
     */
    public function setLocales(array $locales): static;
}
