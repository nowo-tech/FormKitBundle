<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Single translation for DemoTranslatableItem (title, description per locale).
 */
class DemoTranslationItem
{
    public ?string $title       = null;
    public ?string $description = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
