<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use InvalidArgumentException;
use Nowo\FormKitBundle\Css\CssClassUtilities;
use PHPUnit\Framework\TestCase;

final class CssClassUtilitiesTest extends TestCase
{
    public function testTailwindNormalizeColumnClasses(): void
    {
        $u = new CssClassUtilities('tailwind');
        $out = $u->normalizeColumnClasses(['md:col-span-3', 'md:col-span-6', 'p-4']);

        self::assertStringContainsString('md:col-span-6', $out);
        self::assertStringNotContainsString('md:col-span-3', $out);
        self::assertStringContainsString('p-4', $out);
    }

    public function testNoneFrameworkDoesNotMergeColumns(): void
    {
        $u = new CssClassUtilities('none');
        $out = $u->normalizeColumnClasses(['col-md-3', 'col-md-6']);

        self::assertSame('col-md-3 col-md-6', $out);
    }

    public function testNormalizeColumnClassesFromStringSplitsWhitespace(): void
    {
        $u = new CssClassUtilities('bootstrap');
        $out = $u->normalizeColumnClassesFromString("  col-6   col-md-12  ");

        self::assertStringContainsString('col-6', $out);
        self::assertStringContainsString('col-md-12', $out);
    }

    public function testInvalidFrameworkThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CssClassUtilities('bulma');
    }
}
