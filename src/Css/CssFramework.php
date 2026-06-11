<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

/**
 * CSS UI frameworks supported for class normalization / ordering helpers.
 */
enum CssFramework: string
{
    case Bootstrap  = 'bootstrap';
    case Tailwind   = 'tailwind';
    case Foundation = 'foundation';
    case None       = 'none';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
