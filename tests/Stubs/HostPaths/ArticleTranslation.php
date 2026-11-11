<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

/** Standard host translation entity shape. */
class ArticleTranslation
{
    public function __construct(private ?string $locale = null, private ?string $title = null)
    {
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
