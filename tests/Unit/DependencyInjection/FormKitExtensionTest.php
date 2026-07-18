<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FormKitExtensionTest extends TestCase
{
    public function testLoadSetsParametersFromNamedProfilesAndLoadsServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'default_profile' => 'bootstrap',
            'type_map'        => ['address' => 'App\Form\Type\AddressType'],
            'profiles'        => [
                'bootstrap' => [
                    'alias'              => 'bootstrap',
                    'translation_domain' => 'forms',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'text' => ['label' => 'Text'],
                    ],
                ],
            ],
        ]], $container);

        $profiles = $container->getParameter('nowo_form_kit.profiles');
        self::assertSame('bootstrap', $container->getParameter('nowo_form_kit.default_profile'));
        // BC legacy parameters
        self::assertSame('bootstrap', $container->getParameter('nowo_form_kit.default_config'));
        self::assertSame($profiles, $container->getParameter('nowo_form_kit.configs'));
        self::assertSame('bootstrap', $container->getParameter('nowo_form_kit.css_framework'));
        self::assertSame(['address' => 'App\Form\Type\AddressType'], $container->getParameter('nowo_form_kit.type_map'));
        self::assertSame('forms', $profiles['bootstrap']['translation_domain']);
        self::assertFalse($profiles['bootstrap']['constraint_message_convention']);
        self::assertSame([], $profiles['bootstrap']['by_form']);
        self::assertTrue($container->hasDefinition(\Nowo\FormKitBundle\Form\FormOptionsMerger::class));
        self::assertTrue($container->hasDefinition(\Nowo\FormKitBundle\Form\FormTypeMap::class));
    }

    public function testLoadBuildsLegacyDefaultProfileWhenProfilesAreMissing(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'translation_domain' => 'messages',
            'defaults'           => [
                'attr'     => ['class' => 'input'],
                'row_attr' => ['class' => 'row'],
            ],
            'field_types' => [
                'text' => ['help' => 'legacy_help'],
            ],
        ]], $container);

        $profiles = $container->getParameter('nowo_form_kit.profiles');
        self::assertArrayHasKey('default', $profiles);
        self::assertSame('messages', $profiles['default']['translation_domain']);
        self::assertSame('input', $profiles['default']['defaults']['attr']['class']);
        self::assertSame('legacy_help', $profiles['default']['field_types']['text']['help']);
        self::assertFalse($profiles['default']['constraint_message_convention']);
        self::assertSame([], $profiles['default']['by_form']);
    }

    public function testLoadAcceptsLegacyYamlKeys(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'default_config' => 'bootstrap',
            'configs'        => [
                'bootstrap' => [
                    'alias'              => 'bootstrap',
                    'translation_domain' => 'forms',
                    'defaults'           => [
                        'attr'     => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
        ]], $container);

        self::assertSame('bootstrap', $container->getParameter('nowo_form_kit.default_profile'));
        self::assertArrayHasKey('bootstrap', $container->getParameter('nowo_form_kit.profiles'));
    }

    public function testLoadSetsCssFrameworkParameter(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'css_framework' => 'tailwind',
            'profiles'      => [
                'default' => [
                    'alias'              => 'default',
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
        ]], $container);

        self::assertSame('tailwind', $container->getParameter('nowo_form_kit.css_framework'));
    }

    public function testLoadThrowsWhenDefaultProfileIsUnknown(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nowo_form_kit.default_profile "missing" must be a key in nowo_form_kit.profiles');

        $extension->load([[
            'default_profile' => 'missing',
            'profiles'        => [
                'default' => [
                    'alias'              => 'default',
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
        ]], $container);
    }

    public function testGetAliasReturnsConfigurationAlias(): void
    {
        self::assertSame('nowo_form_kit', (new FormKitExtension())->getAlias());
    }
}
