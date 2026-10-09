<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit;

use Nowo\FormKitBundle\DependencyInjection\Compiler\TwigPathsPass;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
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

    public function testBootPublishesStatelessCsrfFormTypes(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('nowo_form_kit.stateless_csrf.form_types', [FormKitExtension::class]);
        $bundle = new NowoFormKitBundle();
        $bundle->setContainer($container);

        try {
            $bundle->boot();
            self::assertSame([FormKitExtension::class], [...StatelessCsrfTokenIdExtension::getExtendedTypes()]);
        } finally {
            StatelessCsrfTokenIdExtension::configureExtendedTypes([]);
        }

        // No parameter (bundle loaded without the extension config): nothing published.
        $bare = new NowoFormKitBundle();
        $bare->setContainer(new ContainerBuilder());
        $bare->boot();
        self::assertSame([], [...StatelessCsrfTokenIdExtension::getExtendedTypes()]);
    }
}
