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
        $processor = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[]]);

        self::assertSame('default', $processed['default_config']);
        self::assertSame([], $processed['type_map']);
        self::assertSame([], $processed['configs']);
        self::assertSame('messages', $processed['translation_domain']);
    }

    public function testRequiresAliasForNamedConfig(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $this->expectException(InvalidConfigurationException::class);
        $processor->processConfiguration($configuration, [[
            'configs' => [
                'default' => [
                    'translation_domain' => 'messages',
                ],
            ],
        ]]);
    }

    public function testProcessesTypeMapAndNamedConfig(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[
            'default_config' => 'bootstrap',
            'type_map' => [
                'address' => 'App\Form\Type\AddressType',
            ],
            'configs' => [
                'bootstrap' => [
                    'alias' => 'bootstrap',
                    'translation_domain' => 'forms',
                    'defaults' => [
                        'attr' => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [
                        'text' => ['label' => 'Text'],
                    ],
                ],
            ],
        ]]);

        self::assertSame('bootstrap', $processed['default_config']);
        self::assertSame('App\Form\Type\AddressType', $processed['type_map']['address']);
        self::assertSame('bootstrap', $processed['configs']['bootstrap']['alias']);
        self::assertSame('forms', $processed['configs']['bootstrap']['translation_domain']);
    }
}
