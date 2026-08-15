<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;

final class HiddenFieldsCsrfTypeTest extends TestCase
{
    public function testConfigureOptionsAndEmptyBlockPrefix(): void
    {
        $type     = $this->createType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([
            'fields' => ['enabled'],
        ]);

        self::assertTrue($resolved['csrf_protection']);
        self::assertSame('csrf_only', $resolved['csrf_token_id']);
        self::assertFalse($resolved['translation_domain']);
        self::assertSame(['enabled'], $resolved['fields']);
        self::assertSame('', $type->getBlockPrefix());
    }

    public function testBuildFormAddsTypedFields(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'enabled',
                HiddenType::class,
                self::callback(static fn (array $options): bool => !array_key_exists('label', $options)
                    && !array_key_exists('help', $options)
                    && !array_key_exists('placeholder', $options)
                    && $options['required'] === false
                    && $options['empty_data'] === ''
                    && $options['translation_domain'] === false),
            );

        $type->buildForm($builder, [
            'fields'        => ['enabled'],
            'field_types'   => [],
            'field_options' => [],
        ]);
    }

    private function createType(): HiddenFieldsCsrfType
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        return new HiddenFieldsCsrfType($merger, new FormTypeMap([]));
    }
}
