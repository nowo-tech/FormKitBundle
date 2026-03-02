<?php

declare(strict_types=1);

namespace App\Model;

class DemoTranslatableItem
{
    /** @var array<string, DemoTranslationItem> */
    public array $translations = [];

    public function __construct()
    {
        foreach (['en', 'es', 'fr'] as $locale) {
            $this->translations[$locale] = new DemoTranslationItem();
        }
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
