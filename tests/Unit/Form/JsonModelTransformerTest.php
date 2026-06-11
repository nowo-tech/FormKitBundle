<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use PHPUnit\Framework\TestCase;

final class JsonModelTransformerTest extends TestCase
{
    public function testTransformEncodesPrettyJson(): void
    {
        $t = new JsonModelTransformer(true, true);

        $encoded = $t->transform(['a' => 'b']);
        self::assertIsString($encoded);
        self::assertStringContainsString('"a"', $encoded);
        self::assertStringContainsString('"b"', $encoded);
    }

    public function testReverseTransformDecodesJsonStringToArray(): void
    {
        $t = new JsonModelTransformer(true, true);

        self::assertSame(['a' => 'b'], $t->reverseTransform('{"a":"b"}'));
        self::assertSame([], $t->reverseTransform(''));
        self::assertSame([], $t->reverseTransform(null));
    }
}
