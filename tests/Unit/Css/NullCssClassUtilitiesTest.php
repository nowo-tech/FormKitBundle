<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Css;

use Nowo\FormKitBundle\Css\NullCssClassUtilities;
use PHPUnit\Framework\TestCase;

final class NullCssClassUtilitiesTest extends TestCase
{
    public function testNormalizeColumnClassesDeduplicatesTokens(): void
    {
        $out = NullCssClassUtilities::normalizeColumnClasses(['col-6', 'col-6', '', '  extra  ']);

        self::assertSame('col-6 extra', $out);
    }

    public function testOrderClassesDeduplicatesAndTrims(): void
    {
        $out = NullCssClassUtilities::orderClasses('  alpha   beta  alpha  ');

        self::assertSame('alpha beta', $out);
    }
}
