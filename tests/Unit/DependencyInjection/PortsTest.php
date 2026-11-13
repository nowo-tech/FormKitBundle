<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\Css\CssClassUtilities;
use Nowo\FormKitBundle\Css\CssClassUtilitiesInterface;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactoryInterface;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactoryInterface;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsMergerInterface;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\FormTypeMapInterface;
use Nowo\FormKitBundle\Form\GetFilterFormFactory;
use Nowo\FormKitBundle\Form\GetFilterFormFactoryInterface;
use Nowo\FormKitBundle\Form\MultiStepFormBuilder;
use Nowo\FormKitBundle\Form\MultiStepFormBuilderInterface;
use Nowo\FormKitBundle\Form\MultiStepWizardSession;
use Nowo\FormKitBundle\Form\MultiStepWizardSessionFactory;
use Nowo\FormKitBundle\Form\MultiStepWizardSessionFactoryInterface;
use Nowo\FormKitBundle\Form\MultiStepWizardSessionInterface;
use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use Nowo\FormKitBundle\HelpModal\HelpModalNonceInterface;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithPortSetter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * REQ-ARCH-001 rule 1: the UI services and factories have interfaces aliased to the default services; a host can
 * replace any of them by aliasing the interface.
 */
final class PortsTest extends TestCase
{
    private function factory(): MultiStepWizardSessionFactoryInterface
    {
        return new MultiStepWizardSessionFactory(new RequestStack());
    }

    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function ports(): iterable
    {
        yield 'constraints' => [ConstraintDefinitionFactoryInterface::class, ConstraintDefinitionFactory::class];
        yield 'merger' => [FormOptionsMergerInterface::class, FormOptionsMerger::class];
        yield 'type map' => [FormTypeMapInterface::class, FormTypeMap::class];
        yield 'css' => [CssClassUtilitiesInterface::class, CssClassUtilities::class];
        yield 'multi-step builder' => [MultiStepFormBuilderInterface::class, MultiStepFormBuilder::class];
        yield 'wizard session factory' => [MultiStepWizardSessionFactoryInterface::class, MultiStepWizardSessionFactory::class];
        yield 'csrf only factory' => [CsrfOnlyFormFactoryInterface::class, CsrfOnlyFormFactory::class];
        yield 'get filter factory' => [GetFilterFormFactoryInterface::class, GetFilterFormFactory::class];
        yield 'help modal nonce' => [HelpModalNonceInterface::class, HelpModalNonce::class];
    }

    /**
     * @param class-string $interface
     * @param class-string $class
     */
    #[DataProvider('ports')]
    public function testEachPortIsAliasedToItsDefaultService(string $interface, string $class): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([], $container);

        self::assertTrue($container->hasAlias($interface), $interface);
        self::assertSame($class, (string) $container->getAlias($interface));
        self::assertTrue($container->hasDefinition($class), 'the old class id still resolves');
        self::assertTrue((new ReflectionClass($class))->implementsInterface($interface));
    }

    public function testTheSessionPortIsImplementedByTheDefaultSession(): void
    {
        $session = $this->factory()->create(['a' => ['label' => 'A', 'fields' => []]], 'w');

        self::assertInstanceOf(MultiStepWizardSession::class, $session);
        self::assertTrue((new ReflectionClass(MultiStepWizardSession::class))->implementsInterface(MultiStepWizardSessionInterface::class));
    }

    public function testAHostOverridesThePortsTheBundleServicesDependOn(): void
    {
        $container = new ContainerBuilder();
        $container->register(ConstraintDefinitionFactory::class, ConstraintDefinitionFactory::class);
        $container->register('app.type_map', HostTypeMap::class);
        $container->setAlias(FormTypeMapInterface::class, 'app.type_map');
        $container->setAlias(ConstraintDefinitionFactoryInterface::class, ConstraintDefinitionFactory::class);
        $container->register(FormOptionsMerger::class, FormOptionsMerger::class)
            ->setAutowired(true)
            ->setArgument('$profiles', [])
            ->setArgument('$defaultProfileName', 'default')
            ->setArgument('$formRegistry', null)
            ->setArgument('$translator', null)
            ->setPublic(true);
        $container->setAlias(FormOptionsMergerInterface::class, FormOptionsMerger::class)->setPublic(true);
        $container->compile();

        $merger = $container->get(FormOptionsMergerInterface::class);

        self::assertInstanceOf(FormOptionsMerger::class, $merger);
        $map = (new ReflectionProperty($merger, 'formTypeMap'))->getValue($merger);
        self::assertInstanceOf(HostTypeMap::class, $map);
    }

    public function testTheInjectorPassFollowsTheMergerAliasForPortTypedSetters(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(FormOptionsMerger::class, new Definition(FormOptionsMerger::class));
        $container->register('dummy.port_setter', DummyFormTypeWithPortSetter::class)->addTag('form.type');

        (new FormOptionsMergerInjectorCompilerPass())->process($container);

        $calls = $container->getDefinition('dummy.port_setter')->getMethodCalls();
        self::assertSame('setFormOptionsMerger', $calls[0][0]);
        self::assertInstanceOf(Reference::class, $calls[0][1][0]);
        self::assertSame(FormOptionsMergerInterface::class, (string) $calls[0][1][0]);
    }
}

final readonly class HostTypeMap implements FormTypeMapInterface
{
    public function resolve(string $snakeCaseType): ?string
    {
        return null;
    }

    public function typeNames(): array
    {
        return [];
    }
}
