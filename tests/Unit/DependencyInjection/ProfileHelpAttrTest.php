<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ProfileHelpAttrTest extends TestCase
{
    public function testProfileHelpAttrAndCssFramework(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([[
            'default_profile' => 'tw',
            'profiles'        => [
                'tw'    => ['alias' => 'tw', 'help_attr' => ['class' => 'text-sm', 'data-x' => '1'], 'css_framework' => 'tailwind', 'defaults' => ['help_attr' => ['class' => 'text-xs']]],
                'bs'    => ['alias' => 'bs', 'help_attr' => ['class' => 'form-text']],
                'plain' => ['alias' => 'plain'],
            ],
        ]], $container);

        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = $container->getParameter('nowo_form_kit.profiles');
        self::assertSame(['class' => 'text-xs', 'data-x' => '1'], $profiles['tw']['defaults']['help_attr'], 'defaults.help_attr wins per attribute');
        self::assertSame('tailwind', $profiles['tw']['css_framework']);
        self::assertSame(['class' => 'form-text'], $profiles['bs']['defaults']['help_attr']);
        self::assertNull($profiles['bs']['css_framework']);
        self::assertSame([], $profiles['plain']['defaults']['help_attr']);
        self::assertNull($profiles['filter']['css_framework']);
    }
}
