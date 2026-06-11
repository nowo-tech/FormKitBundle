<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Type\StaticHtmlType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StaticHtmlTypeTest extends TestCase
{
    public function testConfigureOptionsDefaultsAndBlockPrefix(): void
    {
        $type     = new StaticHtmlType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([]);
        self::assertFalse($resolved['mapped']);
        self::assertFalse($resolved['label']);
        self::assertFalse($resolved['required']);
        self::assertSame('<div class="w-100"></div>', $resolved['html']);

        self::assertSame('static_html', $type->getBlockPrefix());
    }
}
