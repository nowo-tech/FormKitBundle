<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

/** DDD host per-locale DTO. */
final class TranslationInput
{
    public function __construct(public ?string $locale = null, public ?string $title = null)
    {
    }
}
