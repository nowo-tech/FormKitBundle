<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use Nowo\FormKitBundle\Css\FoundationCssClassUtilities;
use PHPUnit\Framework\TestCase;

final class FoundationCssClassUtilitiesTest extends TestCase
{
    public function testNormalizeColumnClassesKeepsLargestNumberPerBreakpoint(): void
    {
        $out = FoundationCssClassUtilities::normalizeColumnClasses([
            'medium-3',
            'medium-8',
            'cell',
        ]);

        self::assertStringContainsString('medium-8', $out);
        self::assertStringNotContainsString('medium-3', $out);
        self::assertStringContainsString('cell', $out);
    }

    public function testOrderClassesBucketsFoundationCategories(): void
    {
        $out = FoundationCssClassUtilities::orderClasses(
            'custom-x button primary margin-1 font-bold grid-x cell medium-6 align-middle position-relative padding-2 border rounded show-for-medium',
        );

        self::assertStringStartsWith('grid-x cell medium-6', $out);
        self::assertStringContainsString('position-relative', $out);
        self::assertStringContainsString('align-middle', $out);
        self::assertStringContainsString('margin-1', $out);
        self::assertStringContainsString('padding-2', $out);
        self::assertStringContainsString('font-bold', $out);
        self::assertStringContainsString('primary', $out);
        self::assertStringContainsString('border', $out);
        self::assertStringContainsString('button', $out);
        self::assertStringContainsString('show-for-medium', $out);
        self::assertStringContainsString('custom-x', $out);
    }

    public function testOrderClassesReturnsEmptyStringForBlankInput(): void
    {
        self::assertSame('', FoundationCssClassUtilities::orderClasses('   '));
    }

    public function testNormalizeColumnClassesSkipsEmptyTokens(): void
    {
        $out = FoundationCssClassUtilities::normalizeColumnClasses(['', '  ', 'small-4']);

        self::assertSame('small-4', $out);
    }

    public function testOrderClassesBucketsAlignUtilitiesIntoFlexCategory(): void
    {
        $out = FoundationCssClassUtilities::orderClasses('align-middle cell');

        self::assertSame('cell align-middle', $out);
    }
}
