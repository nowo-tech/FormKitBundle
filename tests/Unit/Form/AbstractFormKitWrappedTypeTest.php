<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\AbstractFormKitWrappedType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class AbstractFormKitWrappedTypeTest extends TestCase
{
    public function testDelegatesParentToInnerTypeAndUsesSnakeCaseBlockPrefix(): void
    {
        $type = new DemoWrappedFieldType();

        self::assertSame(TextType::class, $type->getParent());
        self::assertSame('demo_wrapped_field', $type->getBlockPrefix());
    }
}

final class DemoWrappedFieldType extends AbstractFormKitWrappedType
{
    protected function getInnerType(): string
    {
        return TextType::class;
    }
}

