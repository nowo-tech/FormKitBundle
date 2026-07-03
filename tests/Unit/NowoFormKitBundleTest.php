<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\NowoFormKitBundle;
use PHPUnit\Framework\TestCase;

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

    public function testBuildRegistersFormOptionsMergerInjectorCompilerPass(): void
    {
        $bundle    = new NowoFormKitBundle();
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getPasses();
        $found  = false;
        foreach ($passes as $pass) {
            if ($pass instanceof \Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }
}
