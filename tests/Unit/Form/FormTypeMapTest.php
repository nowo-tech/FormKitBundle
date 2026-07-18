<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;

use function dirname;

final class FormTypeMapTest extends TestCase
{
    public function testResolveReturnsFqcnForBuiltinType(): void
    {
        $map = new FormTypeMap([]);
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\TextType::class, $map->resolve('text'));
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\EmailType::class, $map->resolve('email'));
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
        $map   = new FormTypeMap([]);
        $names = $map->typeNames();
        self::assertContains('text', $names);
        self::assertContains('email', $names);
        self::assertContains('date', $names);
        self::assertContains('money', $names);
        self::assertContains('collection', $names);
        self::assertContains('tel', $names);
    }

    public function testResolveReturnsFqcnForExtendedBuiltinTypes(): void
    {
        $map = new FormTypeMap([]);
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\DateType::class, $map->resolve('date'));
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\MoneyType::class, $map->resolve('money'));
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\CollectionType::class, $map->resolve('collection'));
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
    public function testOptionalTypesAreRegisteredWhenClassesExist(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

        $map = new FormTypeMap([]);

        self::assertSame(\Symfony\UX\Dropzone\Form\DropzoneType::class, $map->resolve('dropzone'));
        self::assertContains('dropzone', $map->typeNames());
    }
}
