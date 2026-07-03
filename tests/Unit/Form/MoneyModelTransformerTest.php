<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
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

    public function testConstructorRejectsNegativeScale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MoneyModelTransformer(-1);
    }

    public function testTransformWithZeroScaleReturnsIntegerString(): void
    {
        $t = new MoneyModelTransformer(0);

        self::assertSame('1234', $t->transform(1234));
    }

    public function testReverseTransformAcceptsIntegerAndZeroScale(): void
    {
        $t = new MoneyModelTransformer(0);

        self::assertSame(99, $t->reverseTransform(99));
        self::assertSame(100, $t->reverseTransform('100'));
    }

    public function testReverseTransformThrowsForInvalidInputType(): void
    {
        $t = new MoneyModelTransformer(2);

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $t->reverseTransform(['bad']);
    }

    public function testReverseTransformThrowsForInvalidFormat(): void
    {
        $t = new MoneyModelTransformer(2);

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $t->reverseTransform('abc');
    }

    public function testTransformCoercesFloatAndDecimalStringModelValues(): void
    {
        $t = new MoneyModelTransformer(2);

        self::assertSame('12.35', $t->transform(12.345));
        self::assertSame('12.34', $t->transform('12.34'));
        self::assertSame('0.00', $t->transform(''));
        self::assertSame('12.34', $t->transform('1234'));
    }

    public function testTransformThrowsForInvalidModelType(): void
    {
        $t = new MoneyModelTransformer(2);

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $t->transform(['bad']);
    }

    public function testReverseTransformRoundsUpWhenFractionCrossesScaleBoundary(): void
    {
        $t = new MoneyModelTransformer(2);

        self::assertSame(100, $t->reverseTransform('0.995'));
    }

    public function testTransformThrowsForInvalidDecimalModelString(): void
    {
        $t = new MoneyModelTransformer(2);

        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $t->transform('not-a-number');
    }
}
