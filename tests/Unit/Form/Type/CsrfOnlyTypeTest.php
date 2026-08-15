<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CsrfOnlyTypeTest extends TestCase
{
    public function testConfigureOptionsAndEmptyBlockPrefix(): void
    {
        $type     = $this->createType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([]);

        self::assertTrue($resolved['csrf_protection']);
        self::assertSame('csrf_only', $resolved['csrf_token_id']);
        self::assertTrue($resolved['allow_extra_fields']);
        self::assertSame('', $type->getBlockPrefix());
    }

    public function testBuildFormIsNoOp(): void
    {
        $type    = $this->createType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())->method('add');

        $type->buildForm($builder, []);
    }

    private function createType(): CsrfOnlyType
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

        return new CsrfOnlyType($merger, new FormTypeMap([]));
    }
}
