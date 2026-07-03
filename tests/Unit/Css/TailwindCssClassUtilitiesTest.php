<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use Nowo\FormKitBundle\Css\TailwindCssClassUtilities;
use PHPUnit\Framework\TestCase;

final class TailwindCssClassUtilitiesTest extends TestCase
{
    public function testNormalizeColumnClassesKeepsLargestSpanPerBreakpoint(): void
    {
        $out = TailwindCssClassUtilities::normalizeColumnClasses([
            'md:col-span-3',
            'md:col-span-6',
            'col-span-12',
            'p-4',
            '',
        ]);

        self::assertStringContainsString('md:col-span-6', $out);
        self::assertStringNotContainsString('md:col-span-3', $out);
        self::assertStringContainsString('col-span-12', $out);
        self::assertStringContainsString('p-4', $out);
    }

    public function testOrderClassesBucketsByCategory(): void
    {
        $out = TailwindCssClassUtilities::orderClasses(
            'shadow-lg custom-x col-span-6 flex mb-2 relative w-full text-sm bg-red-500 border rounded transition-opacity',
        );

        self::assertSame(
            'col-span-6 flex relative mb-2 w-full text-sm bg-red-500 border rounded shadow-lg transition-opacity custom-x',
            $out,
        );
    }

    public function testOrderClassesReturnsEmptyStringForBlankInput(): void
    {
        self::assertSame('', TailwindCssClassUtilities::orderClasses('   '));
    }

    public function testOrderClassesBucketsContainerAndLayoutClasses(): void
    {
        $out = TailwindCssClassUtilities::orderClasses('hidden container mx-auto');

        self::assertSame('container mx-auto hidden', $out);
    }
}
