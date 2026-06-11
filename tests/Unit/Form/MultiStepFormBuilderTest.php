<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\MultiStepFormBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class MultiStepFormBuilderTest extends TestCase
{
    public function testCreateStepFormAddsStringAndArrayDefinitions(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'text'   => ['attr' => ['class' => 'text-class']],
                        'choice' => ['attr' => ['class' => 'choice-class']],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
        $factory = $this->createMock(FormFactoryInterface::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $form    = $this->createMock(FormInterface::class);

        $factory->expects(self::once())
            ->method('createBuilder')
            ->with(FormType::class, ['a' => 1], [])
            ->willReturn($builder);

        $builder->expects(self::once())
            ->method('getForm')
            ->willReturn($form);

        $calls = [];
        $builder->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$calls, $builder) {
                self::assertIsArray($opts);
                self::assertSame('messages', $opts['translation_domain'] ?? null);
                $calls[] = [$name, $type];

                return $builder;
            });

        $subject = new MultiStepFormBuilder($factory, $merger);
        $result  = $subject->createStepForm(
            wizardName: 'wiz',
            stepKey: 'step1',
            fieldsDefinition: [
                'full_name' => 'text',
                'topic'     => [
                    'type'    => 'choice',
                    'choices' => ['Support' => 'support'],
                ],
            ],
            data: ['a' => 1],
            configName: null,
        );

        self::assertSame($form, $result);
        self::assertSame([
            ['full_name', 'text'],
            ['topic', 'choice'],
        ], $calls);
    }

    public function testCreateStepFormThrowsWhenTypeMissingInArrayDefinition(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
        $factory = $this->createMock(FormFactoryInterface::class);

        $subject = new MultiStepFormBuilder($factory, $merger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multi-step field "broken" must have a non-empty "type" key.');

        $subject->createStepForm(
            wizardName: 'wiz',
            stepKey: 'step1',
            fieldsDefinition: [
                'broken' => [
                    'choices' => ['A' => 'a'],
                ],
            ],
        );
    }
}
