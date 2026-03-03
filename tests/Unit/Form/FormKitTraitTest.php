<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\FormKitTrait;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class FormKitTraitTest extends TestCase
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
                    'field_types' => [],
                ],
                'compact' => [
                    'translation_domain' => 'compact_forms',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control-sm'],
                        'row_attr' => ['class' => 'mb-1'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
        );
    }

    private function createType(): object
    {
        return new class {
            use FormKitTrait;

            public function getBlockPrefix(): string
            {
                return 'search_form';
            }

            public function addSnakeField(FormBuilderInterface $builder, string $name, string $type, array $options = []): void
            {
                $this->addField($builder, $name, $type, $options);
            }

            public function addFromArray(FormBuilderInterface $builder, array $fields): void
            {
                $this->buildFormFromArray($builder, $fields);
            }
        };
    }

    public function testAddFieldResolvesSnakeCaseAndAppliesConfig(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));
        $type->setFormKitConfigName('compact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'q',
                'Symfony\Component\Form\Extension\Core\Type\TextType',
                self::callback(static function (array $options): bool {
                    return $options['translation_domain'] === 'compact_forms'
                        && $options['label'] === 'search_form.q.label'
                        && ($options['attr']['class'] ?? '') === 'form-control-sm';
                }),
            );

        $type->addSnakeField($builder, 'q', 'text');
    }

    public function testAddFieldThrowsForUnknownType(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown form type snake_case name "missing_type".');
        $type->addSnakeField($builder, 'foo', 'missing_type');
    }

    public function testBuildFormFromArraySupportsMixedDefinitions(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::logicalOr('q', 'topic'),
                self::logicalOr(
                    'Symfony\Component\Form\Extension\Core\Type\TextType',
                    'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                ),
                self::isType('array'),
            );

        $type->addFromArray($builder, [
            'q'     => 'text',
            'topic' => [
                'type'    => 'choice',
                'choices' => ['Support' => 'support'],
            ],
        ]);
    }
}
