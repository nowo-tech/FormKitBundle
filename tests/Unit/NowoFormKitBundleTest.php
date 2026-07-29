<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit;

use Nowo\FormKitBundle\DependencyInjection\Compiler\TwigPathsPass;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\NowoFormKitBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NowoFormKitBundleTest extends TestCase
{
    public function testGetContainerExtensionReturnsFormKitExtensionAndIsMemoized(): void
    {
        $bundle = new NowoFormKitBundle();

        $first  = $bundle->getContainerExtension();
        $second = $bundle->getContainerExtension();

        self::assertInstanceOf(FormKitExtension::class, $first);
        self::assertSame($first, $second);
    }

    public function testBuildRegistersTwigPathsPass(): void
    {
        $bundle    = new NowoFormKitBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getPasses();
        $found  = false;
        foreach ($passes as $pass) {
            if ($pass instanceof TwigPathsPass) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testBuildRegistersFormOptionsMergerInjectorCompilerPass(): void
    {
        $bundle    = new NowoFormKitBundle();
        $container = new ContainerBuilder();

        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getPasses();
        $found  = false;
        foreach ($passes as $pass) {
            if ($pass instanceof FormOptionsMergerInjectorCompilerPass) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }
}
