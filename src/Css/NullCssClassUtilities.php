<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

use function in_array;

/**
 * No framework: deduplicate tokens only (no column merge, no category ordering).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class NullCssClassUtilities
{
    /**
     * @param list<string> $classes
     */
    public static function normalizeColumnClasses(array $classes): string
    {
        $out = [];
        foreach ($classes as $c) {
            $c = trim($c);
            if ($c !== '' && !in_array($c, $out, true)) {
                $out[] = $c;
            }
        }

        return implode(' ', $out);
    }

    public static function orderClasses(string $classString): string
    {
        /** @var list<string> $classes */
        $classes = array_values(array_unique(array_filter(array_map(trim(...), explode(' ', $classString)))));

        return implode(' ', $classes);
    }
}
