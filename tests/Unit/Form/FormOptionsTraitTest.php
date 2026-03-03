<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormOptionsTraitTest extends TestCase
{
    private function createMerger(): FormOptionsMerger
    {
        return new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'text' => ['attr' => ['class' => 'form-control text-input']],
                    ],
                ],
                'bootstrap' => [
                    'translation_domain' => 'forms',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control form-control-lg'],
                        'row_attr' => ['class' => 'mb-4'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
        );
    }

    public function testAddWithDefaultsUsesSelectedConfig(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addField(FormBuilderInterface $builder): void
            {
                $this->addWithDefaults($builder, 'name', TextType::class, []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormKitConfigName('bootstrap');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static function (array $options): bool {
                    return $options['translation_domain'] === 'forms'
                        && $options['label'] === 'demo_form.name.label'
                        && ($options['attr']['placeholder'] ?? null) === 'demo_form.name.placeholder'
                        && $options['help'] === 'demo_form.name.help'
                        && ($options['attr']['class'] ?? '') === 'form-control form-control-lg';
                }),
            );

        $type->addField($builder);
    }

    public function testBuildFormFromArraySupportsStringAndArrayDefinitions(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'profile';
            }

            public function addFromArray(FormBuilderInterface $builder): void
            {
                $this->buildFormFromArray($builder, [
                    'full_name' => TextType::class,
                    'topic'     => [
                        'type'    => ChoiceType::class,
                        'choices' => ['Support' => 'support'],
                    ],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::logicalOr('full_name', 'topic'),
                self::logicalOr(TextType::class, ChoiceType::class),
                self::isType('array'),
            );

        $type->addFromArray($builder);
    }

    public function testBuildFormFromArrayThrowsWhenTypeIsMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'profile';
            }

            public function addInvalid(FormBuilderInterface $builder): void
            {
                $this->buildFormFromArray($builder, [
                    'broken' => ['choices' => ['A' => 'a']],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "broken" must have a non-empty "type" key.');
        $type->addInvalid($builder);
    }
}
