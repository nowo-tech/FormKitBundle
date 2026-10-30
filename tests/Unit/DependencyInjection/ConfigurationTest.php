<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
        self::assertSame([
            'enabled'                     => false,
            'token_id'                    => 'submit',
            'form_types'                  => [],
            'register_stateless_token_id' => true,
        ], $processed['stateless_csrf']);
        self::assertSame(['unlabelled_choice_aria_label' => true], $processed['accessibility']);
    }

    public function testAccessibilityUnlabelledChoiceAriaLabelCanBeDisabled(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[
            'accessibility' => ['unlabelled_choice_aria_label' => false],
        ]]);

        self::assertFalse($processed['accessibility']['unlabelled_choice_aria_label']);
    }

    public function testStatelessCsrfRejectsEmptyTokenId(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new Configuration(), [['stateless_csrf' => ['token_id' => '']]]);
    }

    public function testStatelessCsrfAcceptsFormTypes(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[
            'stateless_csrf' => ['enabled' => true, 'form_types' => ['App\\Form\\A']],
        ]]);

        self::assertTrue($processed['stateless_csrf']['enabled']);
        self::assertSame(['App\\Form\\A'], $processed['stateless_csrf']['form_types']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function baseFormTypes(): iterable
    {
        yield 'FormType' => [FormType::class];
        yield 'FormType leading slash' => ['\\' . FormType::class];
        yield 'TextType' => [TextType::class];
        yield 'EntityType (bridge)' => ['Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType'];
        yield 'case-insensitive FQCN' => ['symfony\\component\\form\\extension\\core\\type\\formtype'];
    }

    #[DataProvider('baseFormTypes')]
    public function testStatelessCsrfRejectsBaseFormTypes(string $type): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/stateless_csrf\.form_types must list your own form types/');

        (new Processor())->processConfiguration(new Configuration(), [[
            'stateless_csrf' => ['enabled' => true, 'form_types' => ['App\\Form\\NewsletterType', $type]],
        ]]);
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

    public function testConventionRequiresTranslationDefaults(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[
            'profiles' => ['main' => ['alias' => 'main']],
        ]]);

        self::assertFalse($processed['convention_requires_translation']);
        self::assertNull($processed['profiles']['main']['convention_requires_translation']);
    }

    public function testConventionRequiresTranslationAcceptsBooleans(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[
            'convention_requires_translation' => true,
            'profiles'                        => ['main' => ['alias' => 'main', 'convention_requires_translation' => false]],
        ]]);

        self::assertTrue($processed['convention_requires_translation']);
        self::assertFalse($processed['profiles']['main']['convention_requires_translation']);
    }

    public function testConventionRequiresTranslationRejectsNonBoolean(): void
    {
        $this->expectException(InvalidTypeException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'profiles' => ['main' => ['alias' => 'main', 'convention_requires_translation' => 'yes']],
        ]]);
    }

    public function testGlobalConventionRequiresTranslationRejectsNonBoolean(): void
    {
        $this->expectException(InvalidTypeException::class);

        (new Processor())->processConfiguration(new Configuration(), [['convention_requires_translation' => 'yes']]);
    }
}
