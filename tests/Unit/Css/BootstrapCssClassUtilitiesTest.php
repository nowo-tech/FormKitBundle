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
}
