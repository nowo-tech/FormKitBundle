<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Attribute;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormKitConfigAttributeTest extends TestCase
{
    public function testNameFromReadsAttributeOnClass(): void
    {
        $type = new #[FormKitConfig('bootstrap')] class {
        };

        self::assertSame('bootstrap', FormKitConfig::nameFrom($type));
    }

    public function testNameFromReturnsNullWhenAbsent(): void
    {
        self::assertNull(FormKitConfig::nameFrom(new class {
        }));
    }

    public function testNameFromReadsAttributeOnParentClass(): void
    {
        $type = new class extends FormKitConfigParentStub {
        };

        self::assertSame('from_parent', FormKitConfig::nameFrom($type));
    }

    public function testTraitUsesAttributeAsConfigName(): void
    {
        $type = new #[FormKitConfig('bootstrap')] class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'attr_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addText($builder, 'title');
            }

            public function exposeConfig(): ?string
            {
                return $this->resolvedFormKitConfigName();
            }
        };

        $type->setFormOptionsMerger(new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain'            => 'messages',
                    'defaults'                      => ['attr' => [], 'row_attr' => []],
                    'field_types'                   => [],
                    'constraint_message_convention' => false,
                    'by_form'                       => [],
                ],
                'bootstrap' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control-lg'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types'                   => [],
                    'constraint_message_convention' => false,
                    'by_form'                       => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        ));

        self::assertSame('bootstrap', $type->exposeConfig());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'title',
                TextType::class,
                self::callback(static fn (array $options): bool => ($options['attr']['class'] ?? null) === 'form-control-lg'),
            );

        $type->run($builder);
    }

    public function testExplicitSetterOverridesAttribute(): void
    {
        $type = new #[FormKitConfig('bootstrap')] class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'attr_demo';
            }

            public function exposeConfig(): ?string
            {
                return $this->resolvedFormKitConfigName();
            }
        };

        $type->setFormKitConfigName('default');

        self::assertSame('default', $type->exposeConfig());
    }
}

#[FormKitConfig('from_parent')]
abstract class FormKitConfigParentStub extends AbstractType
{
}
