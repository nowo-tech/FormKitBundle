<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Type\StaticSeparatorType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StaticSeparatorTypeTest extends TestCase
{
    public function testConfigureOptionsDefaultsAndBlockPrefix(): void
    {
        $type     = new StaticSeparatorType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([]);
        self::assertFalse($resolved['mapped']);
        self::assertFalse($resolved['label']);
        self::assertFalse($resolved['required']);

        self::assertSame('static_separator', $type->getBlockPrefix());
    }

    public function testBuildFormIsNoOp(): void
    {
        $type    = new StaticSeparatorType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())->method('add');

        $type->buildForm($builder, []);
    }
}
