<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** Standard host entity shape (ORM mapping omitted): translations indexed by locale. */
class Article
{
    private ?string $code = null;

    /** @var Collection<string, ArticleTranslation> */
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    /** @return Collection<string, ArticleTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ArticleTranslation $translation): void
    {
        $this->translations->set((string) $translation->getLocale(), $translation);
    }

    public function removeTranslation(ArticleTranslation $translation): void
    {
        $this->translations->removeElement($translation);
    }
}
