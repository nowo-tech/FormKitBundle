<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

/** DDD host input DTO: scalars, a value object and per-locale DTOs keyed by locale. */
final class RegisterCustomerInput
{
    public ?string $name = null;

    public ?EmailAddress $email = null;

    /** @var array<string, TranslationInput> */
    public array $translations = [];
}
