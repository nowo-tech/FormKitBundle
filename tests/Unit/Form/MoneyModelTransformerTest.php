<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use PHPUnit\Framework\TestCase;

final class MoneyModelTransformerTest extends TestCase
{
    public function testTransformCentsToDecimalString(): void
    {
        $t = new MoneyModelTransformer(2);

        self::assertSame('', $t->transform(null));
        self::assertSame('12.34', $t->transform(1234));
        self::assertSame('-12.34', $t->transform(-1234));
    }

    public function testReverseTransformDecimalStringToCents(): void
    {
        $t = new MoneyModelTransformer(2);

        self::assertSame(1234, $t->reverseTransform('12.34'));
        self::assertSame(1234, $t->reverseTransform('12,34'));
        self::assertSame(-1234, $t->reverseTransform('-12.34'));
        self::assertNull($t->reverseTransform(''));
        self::assertNull($t->reverseTransform(null));
    }

    public function testReverseTransformRoundsToScale(): void
    {
        $t = new MoneyModelTransformer(2);

        self::assertSame(1235, $t->reverseTransform('12.345'));
        self::assertSame(1234, $t->reverseTransform('12.344'));
    }
}

