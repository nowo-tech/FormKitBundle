<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
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
}

final class DummyFormTypeWithSetter
{
    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
    }
}

final class DummyFormTypeWithoutSetter
{
}
