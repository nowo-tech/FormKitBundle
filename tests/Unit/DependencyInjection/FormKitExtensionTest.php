<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FormKitExtensionTest extends TestCase
{
    public function testLoadSetsParametersFromNamedConfigsAndLoadsServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'default_config' => 'bootstrap',
            'type_map' => ['address' => 'App\Form\Type\AddressType'],
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
        ]], $container);

        $configs = $container->getParameter('nowo_form_kit.configs');
        self::assertSame('bootstrap', $container->getParameter('nowo_form_kit.default_config'));
        self::assertSame(['address' => 'App\Form\Type\AddressType'], $container->getParameter('nowo_form_kit.type_map'));
        self::assertSame('forms', $configs['bootstrap']['translation_domain']);
        self::assertTrue($container->hasDefinition('Nowo\FormKitBundle\Form\FormOptionsMerger'));
        self::assertTrue($container->hasDefinition('Nowo\FormKitBundle\Form\FormTypeMap'));
    }

    public function testLoadBuildsLegacyDefaultConfigWhenConfigsAreMissing(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'translation_domain' => 'messages',
            'defaults' => [
                'attr' => ['class' => 'input'],
                'row_attr' => ['class' => 'row'],
            ],
            'field_types' => [
                'text' => ['help' => 'legacy_help'],
            ],
        ]], $container);

        $configs = $container->getParameter('nowo_form_kit.configs');
        self::assertArrayHasKey('default', $configs);
        self::assertSame('messages', $configs['default']['translation_domain']);
        self::assertSame('input', $configs['default']['defaults']['attr']['class']);
        self::assertSame('legacy_help', $configs['default']['field_types']['text']['help']);
    }

    public function testLoadThrowsWhenDefaultConfigIsUnknown(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nowo_form_kit.default_config "missing" must be a key in nowo_form_kit.configs');

        $extension->load([[
            'default_config' => 'missing',
            'configs' => [
                'default' => [
                    'alias' => 'default',
                    'translation_domain' => 'messages',
                    'defaults' => [
                        'attr' => [],
                        'row_attr' => [],
                    ],
                    'field_types' => [],
                ],
            ],
        ]], $container);
    }
}
