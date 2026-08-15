<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\SearchQueryType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

final class SearchQueryTypeTest extends TestCase
{
    public function testConfigureOptionsInheritGetFilterDefaults(): void
    {
        $type     = $this->createType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([
            'q'          => 'beacon',
            'input_attr' => ['class' => 'input'],
        ]);

        self::assertFalse($resolved['csrf_protection']);
        self::assertSame('GET', $resolved['method']);
        self::assertSame('beacon', $resolved['q']);
        self::assertSame(['class' => 'input'], $resolved['input_attr']);
        self::assertSame('', $type->getBlockPrefix());
    }

    public function testBuildFormAddsSearchField(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'q',
                SearchType::class,
                self::callback(static fn (array $options): bool => $options['data'] === 'hello'
                    && !array_key_exists('placeholder', $options)
                    && !array_key_exists('help', $options)
                    && ($options['attr']['type'] ?? null) === 'search'
                    && ($options['attr']['class'] ?? null) === 'input'),
            );

        $type->buildForm($builder, [
            'q'          => 'hello',
            'input_attr' => ['class' => 'input'],
        ]);
    }

    private function createType(): SearchQueryType
    {
        $merger = new FormOptionsMerger(
            [
                'filter' => [
                    'translation_domain' => 'form',
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

        return new SearchQueryType($merger, new FormTypeMap([]));
    }
}
