<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testProcessesDefaultConfigValues(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[]]);

        self::assertSame('default', $processed['default_profile']);
        self::assertSame([], $processed['type_map']);
        self::assertArrayHasKey('default', $processed['profiles']);
        self::assertSame('default', $processed['profiles']['default']['alias']);
        self::assertSame('messages', $processed['profiles']['default']['translation_domain']);
    }

    public function testRequiresAliasForNamedProfile(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $this->expectException(InvalidConfigurationException::class);
        $processor->processConfiguration($configuration, [[
            'profiles' => [
                'default' => [
                    'translation_domain' => 'messages',
                ],
            ],
        ]]);
    }

    public function testProcessesTypeMapAndNamedProfile(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[
            'default_profile' => 'bootstrap',
            'type_map'        => [
                'address' => 'App\Form\Type\AddressType',
            ],
            'profiles' => [
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
        ]]);

        self::assertSame('bootstrap', $processed['default_profile']);
        self::assertSame('App\Form\Type\AddressType', $processed['type_map']['address']);
        self::assertSame('bootstrap', $processed['profiles']['bootstrap']['alias']);
        self::assertSame('forms', $processed['profiles']['bootstrap']['translation_domain']);
    }

    public function testAcceptsLegacyDefaultConfigAndConfigsKeys(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[
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
        ]]);

        self::assertSame('bootstrap', $processed['default_profile']);
        self::assertArrayHasKey('bootstrap', $processed['profiles']);
        self::assertArrayNotHasKey('default_config', $processed);
        self::assertArrayNotHasKey('configs', $processed);
    }

    public function testLegacyRootKeysNormalizeIntoDefaultProfile(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[
            'translation_domain' => 'legacy_forms',
            'defaults'           => [
                'attr'     => ['class' => 'input'],
                'row_attr' => [],
            ],
            'field_types' => [
                'text' => ['help' => 'legacy_help'],
            ],
        ]]);

        self::assertSame('default', $processed['default_profile']);
        self::assertSame('legacy_forms', $processed['profiles']['default']['translation_domain']);
        self::assertSame('input', $processed['profiles']['default']['defaults']['attr']['class']);
        self::assertSame('legacy_help', $processed['profiles']['default']['field_types']['text']['help']);
    }

    public function testAcceptsLabelAndRequiredUnderDefaultsAndFieldTypes(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[
            'profiles' => [
                'filter' => [
                    'alias'    => 'filter',
                    'defaults' => [
                        'attr'     => ['class' => 'input'],
                        'row_attr' => [],
                        'label'    => false,
                        'required' => false,
                    ],
                    'field_types' => [
                        'choice' => [
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ]]);

        self::assertFalse($processed['profiles']['filter']['defaults']['label']);
        self::assertFalse($processed['profiles']['filter']['defaults']['required']);
        self::assertTrue($processed['profiles']['filter']['field_types']['choice']['required']);
    }
}
