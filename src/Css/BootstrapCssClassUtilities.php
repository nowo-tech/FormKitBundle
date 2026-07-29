<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

/**
 * Bootstrap 5 grid and utility class helpers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class BootstrapCssClassUtilities
{
    /**
     * Normalizes Bootstrap col-* classes: orders by breakpoint and deduplicates per breakpoint.
     *
     * Sorts column classes by breakpoint (base, sm, md, lg, xl, xxl) and keeps only one class
     * per breakpoint, choosing the one with the largest width (e.g. col-md-6 wins over col-md-3).
     * Non-col classes are appended unchanged after the ordered col classes.
     *
     * @param list<string> $classes List of CSS class names (e.g. from a class attribute string split by spaces)
     */
    public static function normalizeColumnClasses(array $classes): string
    {
        $breakpointOrder = ['', 'sm', 'md', 'lg', 'xl', 'xxl'];
        $colPattern      = '/^col(?:-(sm|md|lg|xl|xxl))?(?:-(\d+))?$/';

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
                $bp    = $m[1] ?? '';
                $width = isset($m[2]) ? (int) $m[2] : 0;

                if (!isset($byBreakpoint[$bp]) || $width > $byBreakpoint[$bp]['width']) {
                    $byBreakpoint[$bp] = ['class' => $class, 'width' => $width];
                }
            } else {
                $others[] = $class;
            }
        }

        $orderedCols = [];
        foreach ($breakpointOrder as $bp) {
            if (isset($byBreakpoint[$bp])) {
                $orderedCols[] = $byBreakpoint[$bp]['class'];
            }
        }

        return implode(' ', array_merge($orderedCols, $others));
    }

    /**
     * Orders Bootstrap CSS classes by category for consistent, maintainable markup.
     *
     * Accepts a space-separated class string, removes duplicates, and returns classes
     * in a conventional order: layout/display → flex → position → spacing → sizing →
     * typography → colors → borders → grid → components → utilities → other.
     *
     * @param string $classString Space-separated CSS class names (e.g. from attr['class'])
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
            'layout'     => [],
            'flex'       => [],
            'position'   => [],
            'spacing'    => [],
            'sizing'     => [],
            'typography' => [],
            'colors'     => [],
            'borders'    => [],
            'grid'       => [],
            'components' => [],
            'utilities'  => [],
            'other'      => [],
        ];

        foreach ($classes as $class) {
            if ($class === '') {
                continue;
            }

            if (preg_match('/^d-[a-z]/', $class)) {
                $categories['layout'][] = $class;
            } elseif (preg_match('/^(flex|align-|justify-|gap-|order-)/', $class)) {
                $categories['flex'][] = $class;
            } elseif (preg_match('/^(position-|top-|bottom-|start-|end-|translate-)/', $class)) {
                $categories['position'][] = $class;
            } elseif (preg_match('/^[mp][tbsexy]?-|^gap-|^gx-|^gy-/', $class)) {
                $categories['spacing'][] = $class;
            } elseif (preg_match('/^(w-|h-|min-vw|max-vw|min-vh|max-vh|vw-|vh-)/', $class)) {
                $categories['sizing'][] = $class;
            } elseif (preg_match('/^(fs-|fw-|fst-|lh-|text-)/', $class)) {
                $categories['typography'][] = $class;
            } elseif (preg_match('/^bg-/', $class)) {
                $categories['colors'][] = $class;
            } elseif (preg_match('/^border|^rounded/', $class)) {
                $categories['borders'][] = $class;
            } elseif ($class === 'row' || preg_match('/^col(?:-|$)/', $class)) {
                $categories['grid'][] = $class;
            } elseif (preg_match('/^(btn|form-|input-group|card|nav-|badge|alert|list-group|table|modal|dropdown|tooltip)/', $class)) {
                $categories['components'][] = $class;
            } elseif (preg_match('/^(overflow-|shadow|opacity|visible|invisible)/', $class)) {
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
