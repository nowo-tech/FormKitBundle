<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use PHPUnit\Framework\TestCase;

final class BoolModelTransformerTest extends TestCase
{
    public function testTransformModelOn(): void
    {
        $t = new BoolModelTransformer(1, 0);

        self::assertTrue($t->transform(true));
        self::assertTrue($t->transform(1));
        self::assertTrue($t->transform('1'));
        self::assertTrue($t->transform('true'));

        self::assertFalse($t->transform(false));
        self::assertFalse($t->transform(null));
        self::assertFalse($t->transform(0));
        self::assertFalse($t->transform('0'));
        self::assertFalse($t->transform('false'));
    }

    public function testReverseTransformView(): void
    {
        $t = new BoolModelTransformer(1, 0);

        self::assertSame(1, $t->reverseTransform(true));
        self::assertSame(0, $t->reverseTransform(false));
        self::assertSame(1, $t->reverseTransform('true'));
        self::assertSame(0, $t->reverseTransform('false'));
        self::assertSame(1, $t->reverseTransform(1));
        self::assertSame(0, $t->reverseTransform(0));
        self::assertSame(0, $t->reverseTransform(''));
        self::assertSame(0, $t->reverseTransform(null));
    }
}
