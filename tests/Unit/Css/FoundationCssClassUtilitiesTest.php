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
}
