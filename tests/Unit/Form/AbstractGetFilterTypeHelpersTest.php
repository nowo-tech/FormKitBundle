<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Tests\Stubs\DemoGetFilterType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

final class AbstractGetFilterTypeHelpersTest extends TestCase
{
    public function testAddHiddenFilterFieldDisablesPlaceholderAndHelp(): void
    {
        $type    = $this->createConcreteType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'page',
                HiddenType::class,
                self::callback(static fn (array $options): bool => $options['label'] === false
                    && $options['required'] === false
                    && !array_key_exists('help', $options)
                    && !array_key_exists('placeholder', $options)),
            );

        $type->exposeWithBuilder($builder, static function () use ($type): void {
            $type->exposeAddHiddenFilterField('page');
        });
    }

    public function testAddFilterSelectUsesCataloguePlaceholderByDefault(): void
    {
        $type    = $this->createConcreteType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'status',
                ChoiceType::class,
                self::callback(static fn (array $options): bool => $options['placeholder'] === 'demo_filter.status.placeholder'
                    && ($options['choices']['Open'] ?? null) === 'open'),
            );

        $type->exposeWithBuilder($builder, static function () use ($type): void {
            $type->exposeAddFilterSelect('status', [
                'choices' => ['Open' => 'open'],
            ]);
        });
    }

    public function testAddFilterSelectCanDisableEmptyOption(): void
    {
        $type    = $this->createConcreteType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'status',
                ChoiceType::class,
                self::callback(static fn (array $options): bool => !isset($options['placeholder']) || $options['placeholder'] === false),
            );

        $type->exposeWithBuilder($builder, static function () use ($type): void {
            $type->exposeAddFilterSelect('status', [
                'choices'     => ['Open' => 'open'],
                'placeholder' => false,
            ]);
        });
    }

    public function testDefaultsDisableCsrfUseGetAndNullDataClass(): void
    {
        $type     = $this->createConcreteType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([]);

        self::assertFalse($resolved['csrf_protection']);
        self::assertSame('GET', $resolved['method']);
        self::assertNull($resolved['data_class']);
        self::assertSame('filter', FormKitConfig::nameFrom($type));
    }

    private function createConcreteType(): DemoGetFilterType
    {
        $merger = new FormOptionsMerger(
            [
                'filter' => [
                    'translation_domain' => 'form',
                    'auto_placeholder'   => true,
                    'auto_help'          => true,
                    'defaults'           => [
                        'label'    => false,
                        'required' => false,
                        'attr'     => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
            'filter',
            new ConstraintDefinitionFactory(),
        );

        return new DemoGetFilterType($merger, new FormTypeMap([]));
    }
}
