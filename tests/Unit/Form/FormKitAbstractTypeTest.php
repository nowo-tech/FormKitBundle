<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class FormKitAbstractTypeTest extends TestCase
{
    public function testConstructorInjectsDependenciesAndCanAddField(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults' => [
                        'attr' => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default'
        );

        $map = new FormTypeMap([]);

        $type = new class ($merger, $map) extends FormKitAbstractType {
            public function getBlockPrefix(): string
            {
                return 'contact_form';
            }

            public function buildDemoField(FormBuilderInterface $builder): void
            {
                $this->addText($builder, 'name');
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                'Symfony\Component\Form\Extension\Core\Type\TextType',
                self::callback(static function (array $options): bool {
                    return $options['label'] === 'contact_form.name.label'
                        && ($options['attr']['class'] ?? '') === 'form-control';
                })
            );

        $type->buildDemoField($builder);
    }
}
