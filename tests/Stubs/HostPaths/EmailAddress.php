<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

use InvalidArgumentException;

use function filter_var;
use function strtolower;

use const FILTER_VALIDATE_EMAIL;

/** DDD host value object. */
final readonly class EmailAddress
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid e-mail address.');
        }

        return new self(strtolower($value));
    }
}
