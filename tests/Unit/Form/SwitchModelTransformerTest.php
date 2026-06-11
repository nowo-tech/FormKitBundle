<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use PHPUnit\Framework\TestCase;

final class SwitchModelTransformerTest extends TestCase
{
    public function testTransformModelOn(): void
    {
        $t = new SwitchModelTransformer(1);

        self::assertSame([1], $t->transform(1));
        self::assertSame([1], $t->transform(true));
        self::assertSame([1], $t->transform('1'));
        self::assertSame([], $t->transform(0));
        self::assertSame([], $t->transform(null));
        self::assertSame([], $t->transform(false));
    }

    public function testReverseTransformViewOn(): void
    {
        $t = new SwitchModelTransformer(1);

        self::assertSame(1, $t->reverseTransform([1]));
        self::assertSame(1, $t->reverseTransform(['1']));
        self::assertSame(0, $t->reverseTransform([]));
        self::assertSame(0, $t->reverseTransform([0]));
        self::assertSame(0, $t->reverseTransform(['0']));
        self::assertSame(0, $t->reverseTransform(null));
    }
}

