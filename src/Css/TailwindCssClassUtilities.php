<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

/**
 * Tailwind CSS 3+ class helpers (grid/flex/spacing-oriented ordering and col-span normalization).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class TailwindCssClassUtilities
{
    /** @var list<string> */
    private const BREAKPOINT_ORDER = ['', 'sm', 'md', 'lg', 'xl', '2xl'];

    /**
     * Keeps one `col-span-*` (or breakpoint-prefixed `md:col-span-*`) per breakpoint, largest span wins.
     * Other classes are preserved after ordered col-span tokens.
     *
     * @param list<string> $classes
     */
    public static function normalizeColumnClasses(array $classes): string
    {
        $colPattern = '/^(?:(sm|md|lg|xl|2xl):)?col-span-(\d+)$/';

        /** @var array<string, array{class: string, width: int}> $byBreakpoint */
        $byBreakpoint = [];
        /** @var list<string> $others */
        $others = [];

        foreach ($classes as $class) {
            $class = trim($class);
            if ($class === '') {
                continue;
            }

            if (preg_match($colPattern, $class, $m)) {
                $bp    = $m[1];
                $width = (int) $m[2];

                if (!isset($byBreakpoint[$bp]) || $width > $byBreakpoint[$bp]['width']) {
                    $byBreakpoint[$bp] = ['class' => $class, 'width' => $width];
                }
            } else {
                $others[] = $class;
            }
        }

        $ordered = [];
        foreach (self::BREAKPOINT_ORDER as $bp) {
            if (isset($byBreakpoint[$bp])) {
                $ordered[] = $byBreakpoint[$bp]['class'];
            }
        }

        return implode(' ', array_merge($ordered, $others));
    }

    /**
     * Orders Tailwind-like utility classes into a stable category order (layout → flex/grid → spacing → …).
     *
     * @param string $classString Space-separated class names
     */
    public static function orderClasses(string $classString): string
    {
        /** @var list<string> $classes */
        $classes = array_values(array_unique(array_filter(array_map(trim(...), explode(' ', $classString)))));

        if ($classes === []) {
            return '';
        }

        /** @var array<string, list<string>> $categories */
        $categories = [
            'container'  => [],
            'layout'     => [],
            'flex_grid'  => [],
            'position'   => [],
            'spacing'    => [],
            'sizing'     => [],
            'typography' => [],
            'colors'     => [],
            'borders'    => [],
            'effects'    => [],
            'trans'      => [],
            'other'      => [],
        ];

        foreach ($classes as $class) {
            $base = preg_replace('/^(?:[a-z0-9]+:)+/', '', $class) ?? $class;

            if (preg_match('/^container$|^mx-auto$/', $base)) {
                $categories['container'][] = $class;
            } elseif (preg_match('/^(block|inline|inline-block|hidden|sr-only|float-|clear-)/', $base)) {
                $categories['layout'][] = $class;
            } elseif (preg_match('/^(flex|inline-flex|grid|inline-grid|table|contents)|^(?:col-|row-)|^(?:sm|md|lg|xl|2xl):(?:col-|row-)|^auto-rows-|^auto-cols-|^gap-|^justify-|^items-|^content-|^place-|^self-|^order-/', $base)) {
                $categories['flex_grid'][] = $class;
            } elseif (preg_match('/^(static|fixed|absolute|relative|sticky)|^(inset-|top-|right-|bottom-|left-|z-)/', $base)) {
                $categories['position'][] = $class;
            } elseif (preg_match('/^([mp][xytrblse]?-|[mp]-|space-[xy]-|scroll-[mp]-)/', $base)) {
                $categories['spacing'][] = $class;
            } elseif (preg_match('/^(w-|h-|min-w-|min-h-|max-w-|max-h-|size-)/', $base)) {
                $categories['sizing'][] = $class;
            } elseif (preg_match('/^(bg-|from-|via-|to-)/', $base)
                || preg_match('/^text-(?:inherit|current|transparent|black|white|(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-)/', $base)) {
                $categories['colors'][] = $class;
            } elseif (preg_match('/^(font-|text-|leading-|tracking-|antialiased|subpixel-antialiased|whitespace-|break-)/', $base)) {
                $categories['typography'][] = $class;
            } elseif (preg_match('/^(border|rounded|divide-|ring|outline)/', $base)) {
                $categories['borders'][] = $class;
            } elseif (preg_match('/^(shadow|opacity-|mix-blend|bg-blend|filter|backdrop-|blur)/', $base)) {
                $categories['effects'][] = $class;
            } elseif (preg_match('/^(transition|duration|ease|delay|animate)/', $base)) {
                $categories['trans'][] = $class;
            } else {
                $categories['other'][] = $class;
            }
        }

        $ordered = [];
        foreach ($categories as $bucket) {
            $ordered = array_merge($ordered, $bucket);
        }

        return implode(' ', $ordered);
    }
}
