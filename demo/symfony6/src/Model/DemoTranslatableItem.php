<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Simple DTO for translations demo (no Doctrine).
 * Holds a collection of translations keyed by locale (subset chosen per session in the demo).
 */
class DemoTranslatableItem
{
    /** @var array<string, DemoTranslationItem> */
    public array $translations = [];

    public function __construct()
    {
    }

    /**
     * @param list<string> $locales
     */
    public static function forLocales(array $locales): self
    {
        $item = new self();
        foreach ($locales as $locale) {
            $item->translations[$locale] = new DemoTranslationItem();
        }

        return $item;
    }

    /** @return array<string, DemoTranslationItem> */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /** @param array<string, DemoTranslationItem> $translations */
    public function setTranslations(array $translations): void
    {
        $this->translations = $translations;
    }
}
