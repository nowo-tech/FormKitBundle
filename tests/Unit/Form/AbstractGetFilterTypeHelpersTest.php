<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
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

    /**
     * @return AbstractGetFilterType&object{
     *     exposeWithBuilder: callable,
     *     exposeAddHiddenFilterField: callable,
     *     exposeAddFilterSelect: callable
     * }
     */
    private function createConcreteType(): AbstractGetFilterType
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

        return new class($merger, new FormTypeMap([])) extends AbstractGetFilterType {
            public function getBlockPrefix(): string
            {
                return 'demo_filter';
            }

            /** @param FormBuilderInterface<mixed> $builder */
            public function exposeWithBuilder(FormBuilderInterface $builder, callable $callback): void
            {
                $this->withBuilder($builder, $callback);
            }

            /** @param array<string, mixed> $options */
            public function exposeAddHiddenFilterField(string $name, array $options = []): void
            {
                $this->addHiddenFilterField($name, $options);
            }

            /** @param array<string, mixed> $options */
            public function exposeAddFilterSelect(string $name, array $options): void
            {
                $this->addFilterSelect($name, $options);
            }
        };
    }
}
