<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithoutSetter;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithPrivateSetter;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithSetter;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithTwoParams;
use Nowo\FormKitBundle\Tests\Stubs\DummyFormTypeWithWrongParamType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FormOptionsMergerInjectorCompilerPassTest extends TestCase
{
    public function testInjectsMergerIntoTaggedFormTypes(): void
    {
        $container = new ContainerBuilder();
        $container->register(ConstraintDefinitionFactory::class, ConstraintDefinitionFactory::class)->setPublic(false);
        $container->setDefinition(FormOptionsMerger::class, (new Definition(FormOptionsMerger::class))
            ->setArguments([[], 'default', new Reference(ConstraintDefinitionFactory::class)])
            ->setPublic(false));

        $container->register('dummy.form_type', DummyFormTypeWithSetter::class)->addTag('form.type');

        $container->register('dummy.no_setter', DummyFormTypeWithoutSetter::class)->addTag('form.type');
        $container->register('dummy.not_public', DummyFormTypeWithSetter::class)->addTag('form.type')->setPublic(false);

        $pass = new FormOptionsMergerInjectorCompilerPass();
        $pass->process($container);

        $defWithSetter = $container->getDefinition('dummy.form_type');
        $calls         = $defWithSetter->getMethodCalls();

        self::assertNotEmpty($calls, 'Expected at least one method call');
        self::assertSame('setFormOptionsMerger', $calls[0][0]);
        self::assertInstanceOf(Reference::class, $calls[0][1][0]);
        self::assertSame(FormOptionsMerger::class, (string) $calls[0][1][0]);

        $defNoSetter = $container->getDefinition('dummy.no_setter');
        self::assertSame([], $defNoSetter->getMethodCalls());
    }

    public function testDoesNothingWhenMergerServiceIsMissing(): void
    {
        $container = new ContainerBuilder();
        $container->register('dummy.form_type', DummyFormTypeWithSetter::class)->addTag('form.type');

        $pass = new FormOptionsMergerInjectorCompilerPass();
        $pass->process($container);

        self::assertSame([], $container->getDefinition('dummy.form_type')->getMethodCalls());
    }

    public function testSkipsDefinitionsWithoutResolvableClass(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(FormOptionsMerger::class, (new Definition(FormOptionsMerger::class))
            ->setArguments([[], 'default', new Reference(ConstraintDefinitionFactory::class)])
            ->setPublic(false));

        $container->register('dummy.null_class', DummyFormTypeWithSetter::class)
            ->addTag('form.type')
            ->setClass(null);

        $container->register('dummy.missing_class', DummyFormTypeWithSetter::class)
            ->addTag('form.type')
            ->setClass('App\\Missing\\FormType');

        $container->register('dummy.private_setter', DummyFormTypeWithPrivateSetter::class)->addTag('form.type');

        $pass = new FormOptionsMergerInjectorCompilerPass();
        $pass->process($container);

        self::assertSame([], $container->getDefinition('dummy.null_class')->getMethodCalls());
        self::assertSame([], $container->getDefinition('dummy.missing_class')->getMethodCalls());
        self::assertSame([], $container->getDefinition('dummy.private_setter')->getMethodCalls());
    }

    public function testSkipsSetterWithWrongSignature(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(FormOptionsMerger::class, (new Definition(FormOptionsMerger::class))
            ->setArguments([[], 'default', new Reference(ConstraintDefinitionFactory::class)])
            ->setPublic(false));

        $container->register('dummy.wrong_param', DummyFormTypeWithWrongParamType::class)->addTag('form.type');
        $container->register('dummy.two_params', DummyFormTypeWithTwoParams::class)->addTag('form.type');

        (new FormOptionsMergerInjectorCompilerPass())->process($container);

        self::assertSame([], $container->getDefinition('dummy.wrong_param')->getMethodCalls());
        self::assertSame([], $container->getDefinition('dummy.two_params')->getMethodCalls());
    }
}
