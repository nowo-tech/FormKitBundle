<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

use InvalidArgumentException;

use function in_array;
use function preg_split;
use function sprintf;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Dispatches column normalization and class ordering to the configured CSS framework
 * (`nowo_form_kit.css_framework`: bootstrap, tailwind, foundation, none).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class CssClassUtilities
{
    public function __construct(private string $framework)
    {
        if (!in_array($framework, CssFramework::values(), true)) {
            throw new InvalidArgumentException(sprintf('Unknown css_framework "%s". Allowed: %s.', $framework, implode(', ', CssFramework::values())));
        }
    }

    public function getFramework(): string
    {
        return $this->framework;
    }

    /**
     * @param list<string> $classes
     */
    public function normalizeColumnClasses(array $classes): string
    {
        return match (CssFramework::from($this->framework)) {
            CssFramework::Bootstrap  => BootstrapCssClassUtilities::normalizeColumnClasses($classes),
            CssFramework::Tailwind   => TailwindCssClassUtilities::normalizeColumnClasses($classes),
            CssFramework::Foundation => FoundationCssClassUtilities::normalizeColumnClasses($classes),
            CssFramework::None       => NullCssClassUtilities::normalizeColumnClasses($classes),
        };
    }

    public function normalizeColumnClassesFromString(string $classString): string
    {
        $parts = preg_split('/\s+/', trim($classString), -1, PREG_SPLIT_NO_EMPTY);
        /** @var list<string> $list */
        $list = $parts !== false ? $parts : [];

        return $this->normalizeColumnClasses($list);
    }

    public function orderClasses(string $classString): string
    {
        return match (CssFramework::from($this->framework)) {
            CssFramework::Bootstrap  => BootstrapCssClassUtilities::orderClasses($classString),
            CssFramework::Tailwind   => TailwindCssClassUtilities::orderClasses($classString),
            CssFramework::Foundation => FoundationCssClassUtilities::orderClasses($classString),
            CssFramework::None       => NullCssClassUtilities::orderClasses($classString),
        };
    }
}
