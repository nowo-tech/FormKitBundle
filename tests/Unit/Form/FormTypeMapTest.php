<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

use function dirname;

final class FormTypeMapTest extends TestCase
{
    public function testResolveReturnsFqcnForBuiltinType(): void
    {
        $map = new FormTypeMap([]);
        self::assertSame(TextType::class, $map->resolve('text'));
        self::assertSame(EmailType::class, $map->resolve('email'));
    }

    public function testResolveReturnsNullForUnknownType(): void
    {
        $map = new FormTypeMap([]);
        self::assertNull($map->resolve('unknown_type'));
    }

    public function testResolveUsesConfigOverride(): void
    {
        $map = new FormTypeMap(['custom' => TextType::class]);
        self::assertSame(TextType::class, $map->resolve('custom'));
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
        self::assertContains('search', $names);
    }

    public function testResolveReturnsFqcnForExtendedBuiltinTypes(): void
    {
        $map = new FormTypeMap([]);
        self::assertSame(DateType::class, $map->resolve('date'));
        self::assertSame(MoneyType::class, $map->resolve('money'));
        self::assertSame(CollectionType::class, $map->resolve('collection'));
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

        self::assertSame(DropzoneType::class, $map->resolve('dropzone'));
        self::assertContains('dropzone', $map->typeNames());
    }

    public function testOptionalTypesCanBeResolvedInSameProcessOnceStubsAreLoaded(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

        $map = new FormTypeMap([]);

        self::assertSame(DropzoneType::class, $map->resolve('dropzone'));
        self::assertSame(CropperType::class, $map->resolve('cropper'));
    }
}
