<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;

class FormTypeMapTest extends TestCase
{
    public function testResolveReturnsFqcnForBuiltinType(): void
    {
        $map = new FormTypeMap([]);
        self::assertSame('Symfony\Component\Form\Extension\Core\Type\TextType', $map->resolve('text'));
        self::assertSame('Symfony\Component\Form\Extension\Core\Type\EmailType', $map->resolve('email'));
    }

    public function testResolveReturnsNullForUnknownType(): void
    {
        $map = new FormTypeMap([]);
        self::assertNull($map->resolve('unknown_type'));
    }

    public function testResolveUsesConfigOverride(): void
    {
        $map = new FormTypeMap(['custom' => 'App\Form\Type\CustomType']);
        self::assertSame('App\Form\Type\CustomType', $map->resolve('custom'));
    }

    public function testTypeNamesReturnsKeys(): void
    {
        $map = new FormTypeMap([]);
        $names = $map->typeNames();
        self::assertContains('text', $names);
        self::assertContains('email', $names);
    }
}
