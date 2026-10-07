<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use LogicException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Tests\Stubs\ExposedFormKitAbstractType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormKitAbstractTypeTest extends TestCase
{
    public function testConstructorInjectsDependenciesAndCanAddField(): void
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

        $map = new FormTypeMap([]);

        $type = new class($merger, $map) extends FormKitAbstractType {
            public function getBlockPrefix(): string
            {
                return 'contact_form';
            }

            /** @param FormBuilderInterface<mixed> $builder */
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
                TextType::class,
                self::callback(static fn (array $options): bool => $options['label'] === 'contact_form.name.label'
                    && ($options['attr']['class'] ?? '') === 'form-control'),
            );

        $type->buildDemoField($builder);
    }

    public function testTwigOwnedChromeOptionsDisablesAllChrome(): void
    {
        $type = $this->createType();

        self::assertSame(
            ['label' => false, 'help' => false, 'placeholder' => false, 'translation_domain' => false],
            $type->exposeTwigOwnedChromeOptions(),
        );
    }

    public function testAddChoiceWithFormPlaceholderUsesConventionKeyByDefault(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'statusCode',
                ChoiceType::class,
                self::callback(static fn (array $options): bool => $options['placeholder'] === 'contact_form.status_code.placeholder'
                    && $options['translation_domain'] === 'form'
                    && !isset($options['attr']['placeholder'])
                    && $options['choices'] === ['A' => 'a']),
            );

        $type->exposeAddChoice($builder, 'statusCode', ['choices' => ['A' => 'a']]);
    }

    public function testAddChoiceWithFormPlaceholderUsesCustomEmptyOptionAndDomain(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'topic',
                ChoiceType::class,
                self::callback(static fn (array $options): bool => $options['placeholder'] === 'custom.empty'
                    && $options['translation_domain'] === 'messages'),
            );

        $type->exposeAddChoice($builder, 'topic', ['placeholder' => 'custom.empty', 'translation_domain' => 'messages']);
    }

    public function testAddChoiceWithFormPlaceholderFalseSkipsEmptyOptionRestore(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'topic',
                ChoiceType::class,
                self::callback(static fn (array $options): bool => !isset($options['placeholder'])
                    && !isset($options['attr']['placeholder'])
                    && $options['translation_domain'] === 'messages'),
            );

        $type->exposeAddChoice($builder, 'topic', ['placeholder' => false, 'label' => false, 'help' => false]);
    }

    public function testAddChoiceWithFormPlaceholderRequiresBoundBuilder(): void
    {
        $type = $this->createType();

        $this->expectException(LogicException::class);
        $type->exposeAddChoiceUnbound('topic');
    }

    private function createType(): ExposedFormKitAbstractType
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

        return new ExposedFormKitAbstractType($merger, new FormTypeMap([]));
    }
}
