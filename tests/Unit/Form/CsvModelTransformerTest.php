<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use PHPUnit\Framework\TestCase;

final class CsvModelTransformerTest extends TestCase
{
    public function testTransformModelArrayToCsv(): void
    {
        $t = new CsvModelTransformer(',', true, false);

        self::assertSame('a,b,c', $t->transform(['a', 'b', 'c']));
        self::assertSame('a,b', $t->transform(['a', '', 'b']));
        self::assertSame('', $t->transform(null));
    }

    public function testReverseTransformCsvToModelArray(): void
    {
        $t = new CsvModelTransformer(',', true, false);

        self::assertSame(['a', 'b', 'c'], $t->reverseTransform('a,b,c'));
        self::assertSame(['a', 'b'], $t->reverseTransform('a,,b'));
        self::assertSame([], $t->reverseTransform(''));
        self::assertSame([], $t->reverseTransform(null));
    }
}
