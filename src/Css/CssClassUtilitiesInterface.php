<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Css;

/**
 * Port: normalises responsive column classes and orders utility classes for the configured CSS framework.
 *
 * The default implementation is {@see CssClassUtilities}; alias this interface to your own service to replace it.
 */
interface CssClassUtilitiesInterface
{
    public function getFramework(): string;

    /**
     * @param list<string> $classes
     */
    public function normalizeColumnClasses(array $classes): string;

    public function normalizeColumnClassesFromString(string $classString): string;

    public function orderClasses(string $classString): string;
}
