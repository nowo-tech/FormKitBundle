<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

/**
 * Foundation 6 XY Grid class helpers (small-*, medium-*, grid-x, cell, flex utilities).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FoundationCssClassUtilities
{
    /** @var list<string> */
    private const BREAKPOINT_ORDER = ['small', 'medium', 'large', 'xlarge', 'xxlarge'];

    /**
     * For each breakpoint (`small-12`, `medium-6`, …), keeps the class with the largest column number.
     * Non-matching classes are appended after.
     *
     * @param list<string> $classes
     */
    public static function normalizeColumnClasses(array $classes): string
    {
        $pattern = '/^(small|medium|large|xlarge|xxlarge)-(\d+)$/';

        /** @var array<string, array{class: string, width: int}> $byBreakpoint */
        $byBreakpoint = [];
        /** @var list<string> $others */
        $others = [];

        foreach ($classes as $class) {
            $class = trim($class);
            if ($class === '') {
                continue;
            }

            if (preg_match($pattern, $class, $m)) {
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
     * Orders Foundation-oriented classes: grid/position → flex → spacing → typography → colors → other.
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
            'grid'       => [],
            'position'   => [],
            'flex'       => [],
            'spacing'    => [],
            'typography' => [],
            'colors'     => [],
            'borders'    => [],
            'components' => [],
            'utilities'  => [],
            'other'      => [],
        ];

        foreach ($classes as $class) {
            if ($class === '') {
                continue;
            }

            if (preg_match('/^(?:grid-x|grid-y|grid-margin-x|grid-padding-x|cell|auto|shrink|grow)$/', $class)
                || preg_match('/^(small|medium|large|xlarge|xxlarge)-\d+$/', $class)) {
                $categories['grid'][] = $class;
            } elseif (preg_match('/^(align-|align-middle|align-top|align-bottom|align-self-|align-center)/', $class)) {
                $categories['flex'][] = $class;
            } elseif (preg_match('/^(position-|float-|sticky)/', $class)) {
                $categories['position'][] = $class;
            } elseif (preg_match('/^(margin-|padding-|m-|p-)/', $class)) {
                $categories['spacing'][] = $class;
            } elseif (preg_match('/^(font-|text-|lead|subheader)/', $class)) {
                $categories['typography'][] = $class;
            } elseif (preg_match('/^(primary|secondary|success|warning|alert|callout)/', $class)) {
                $categories['colors'][] = $class;
            } elseif (preg_match('/^border|^rounded/', $class)) {
                $categories['borders'][] = $class;
            } elseif (preg_match('/^(button|label|menu|accordion|tabs|reveal|dropdown)/', $class)) {
                $categories['components'][] = $class;
            } elseif (preg_match('/^(show-for-|hide-for-|invisible|visible)/', $class)) {
                $categories['utilities'][] = $class;
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
