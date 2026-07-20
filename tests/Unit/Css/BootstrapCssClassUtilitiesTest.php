<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use Nowo\FormKitBundle\Css\BootstrapCssClassUtilities;
use PHPUnit\Framework\TestCase;

final class BootstrapCssClassUtilitiesTest extends TestCase
{
    public function testNormalizeColumnClassesKeepsLargestWidthPerBreakpointAndOrdersBreakpoints(): void
    {
        $out = BootstrapCssClassUtilities::normalizeColumnClasses([
            'col-md-3',
            'col-md-6',
            'extra',
            'col-12',
        ]);

        self::assertStringContainsString('col-md-6', $out);
        self::assertStringNotContainsString('col-md-3', $out);
        self::assertStringContainsString('extra', $out);
        self::assertStringContainsString('col-12', $out);
    }

    public function testOrderClassesBucketsLayoutBeforeGrid(): void
    {
        $out = BootstrapCssClassUtilities::orderClasses('col-6 mb-3 d-flex row');

        self::assertSame('d-flex mb-3 col-6 row', $out);
    }

    public function testOrderClassesCoversRemainingCategories(): void
    {
        $out = BootstrapCssClassUtilities::orderClasses(
            'col-6 row btn-primary overflow-hidden custom-x position-relative top-0 mb-2 w-100 fs-6 bg-light border rounded shadow',
        );

        self::assertStringStartsWith('position-relative top-0', $out);
        self::assertStringContainsString('mb-2', $out);
        self::assertStringContainsString('w-100', $out);
        self::assertStringContainsString('fs-6', $out);
        self::assertStringContainsString('bg-light', $out);
        self::assertStringContainsString('border', $out);
        self::assertStringContainsString('col-6', $out);
        self::assertStringContainsString('row', $out);
        self::assertStringContainsString('btn-primary', $out);
        self::assertStringContainsString('overflow-hidden', $out);
        self::assertStringContainsString('custom-x', $out);
    }

    public function testOrderClassesReturnsEmptyStringForBlankInput(): void
    {
        self::assertSame('', BootstrapCssClassUtilities::orderClasses(''));
    }

    public function testOrderClassesSkipsEmptyClassTokens(): void
    {
        self::assertSame('col-6', BootstrapCssClassUtilities::orderClasses(' col-6 '));
        self::assertSame('col-6', BootstrapCssClassUtilities::orderClasses('col-6  '));
    }

    public function testNormalizeColumnClassesIgnoresEmptyTokens(): void
    {
        self::assertSame('col-6', BootstrapCssClassUtilities::normalizeColumnClasses(['', 'col-6']));
    }

    public function testOrderClassesBucketsFlexUtilities(): void
    {
        $out = BootstrapCssClassUtilities::orderClasses('align-items-center col-6');

        self::assertSame('align-items-center col-6', $out);
    }
}
