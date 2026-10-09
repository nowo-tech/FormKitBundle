<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
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

        /** @var array<string, array<string, mixed>> $profiles */
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
        self::assertTrue($container->hasDefinition(FormOptionsMerger::class));
        self::assertTrue($container->hasDefinition(FormTypeMap::class));
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

        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = $container->getParameter('nowo_form_kit.profiles');
        self::assertArrayHasKey('default', $profiles);
        self::assertSame('messages', $profiles['default']['translation_domain']);
        self::assertSame('input', $profiles['default']['defaults']['attr']['class']);
        self::assertSame('legacy_help', $profiles['default']['field_types']['text']['help']);
        self::assertFalse($profiles['default']['constraint_message_convention']);
        self::assertSame([], $profiles['default']['by_form']);
    }

    public function testLoadBuildsFallbackDefaultProfileWhenProfilesAreExplicitlyEmpty(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([[
            'default_profile'       => 'default',
            'profiles'              => [],
            'translation_domain'    => 'messages',
            'required_label_suffix' => ' *',
            'help_modal'            => ['framework' => 'foundation6'],
            'defaults'              => [
                'attr'     => ['class' => 'input'],
                'row_attr' => ['class' => 'row'],
            ],
            'field_types' => [
                'text' => ['help' => 'legacy_help'],
            ],
            'by_form' => [
                'contact' => [
                    'defaults' => ['attr' => ['autocomplete' => 'on']],
                ],
            ],
        ]], $container);

        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = $container->getParameter('nowo_form_kit.profiles');

        self::assertSame('messages', $profiles['default']['translation_domain']);
        self::assertSame(' *', $profiles['default']['required_label_suffix']);
        self::assertSame('foundation6', $profiles['default']['help_modal']['framework']);
        self::assertSame('input', $profiles['default']['defaults']['attr']['class']);
        self::assertSame('legacy_help', $profiles['default']['field_types']['text']['help']);
        self::assertSame(['autocomplete' => 'on'], $profiles['default']['by_form']['contact']['defaults']['attr']);
    }

    public function testLoadBuildsFallbackDefaultProfileWhenNoConfigsAreProvided(): void
    {
        $container = new ContainerBuilder();
        $extension = new FormKitExtension();

        $extension->load([], $container);

        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = $container->getParameter('nowo_form_kit.profiles');

        self::assertSame('default', $container->getParameter('nowo_form_kit.default_profile'));
        self::assertSame('messages', $profiles['default']['translation_domain']);
        self::assertSame([], $profiles['default']['defaults']['attr']);
        self::assertSame([], $profiles['default']['defaults']['row_attr']);
        self::assertArrayHasKey('filter', $profiles);
        self::assertFalse($profiles['filter']['defaults']['label']);
        self::assertFalse($profiles['filter']['defaults']['required']);
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
        /** @var array<string, array<string, mixed>> $profiles */
        $profiles = $container->getParameter('nowo_form_kit.profiles');
        self::assertArrayHasKey('bootstrap', $profiles);
        self::assertArrayHasKey('filter', $profiles);
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

    public function testPrependRegistersAssetPackageWhenFrameworkIsPresent(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());

        (new FormKitExtension())->prepend($container);

        $configs = $container->getExtensionConfig('framework');
        self::assertSame(
            '/bundles/nowoformkit',
            $configs[0]['assets']['packages']['nowo_form_kit']['base_path'],
        );
    }

    public function testStatelessCsrfDisabledByDefaultRegistersNothing(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([['stateless_csrf' => ['form_types' => ['App\\Form\\NewsletterType']]]], $container);

        self::assertFalse($container->getParameter('nowo_form_kit.stateless_csrf.enabled'));
        self::assertSame([], $container->getParameter('nowo_form_kit.stateless_csrf.form_types'));
        self::assertSame([], $container->findTaggedServiceIds('form.type_extension') === [] ? [] : array_values(array_filter(
            array_keys($container->findTaggedServiceIds('form.type_extension')),
            static fn (string $id): bool => str_starts_with($id, 'nowo_form_kit.stateless_csrf_extension.'),
        )));
    }

    public function testStatelessCsrfRegistersOneExtensionPerFormType(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([[
            'stateless_csrf' => [
                'enabled'    => true,
                'token_id'   => 'public_submit',
                'form_types' => ['App\\Form\\NewsletterType', 'App\\Form\\CommentType', 'App\\Form\\NewsletterType'],
            ],
        ]], $container);

        self::assertTrue($container->getParameter('nowo_form_kit.stateless_csrf.enabled'));
        self::assertSame('public_submit', $container->getParameter('nowo_form_kit.stateless_csrf.token_id'));
        self::assertSame(['App\\Form\\NewsletterType', 'App\\Form\\CommentType'], $container->getParameter('nowo_form_kit.stateless_csrf.form_types'));

        $first = $container->getDefinition('nowo_form_kit.stateless_csrf_extension.0');
        self::assertSame(StatelessCsrfTokenIdExtension::class, $first->getClass());
        self::assertSame(['public_submit'], $first->getArguments());
        self::assertSame([['extended_type' => 'App\\Form\\NewsletterType']], $first->getTag('form.type_extension'));
        self::assertSame([['extended_type' => 'App\\Form\\CommentType']], $container->getDefinition('nowo_form_kit.stateless_csrf_extension.1')->getTag('form.type_extension'));
        self::assertFalse($container->hasDefinition('nowo_form_kit.stateless_csrf_extension.2'));
    }

    public function testPrependRegistersStatelessTokenIdWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());
        $container->prependExtensionConfig('nowo_form_kit', ['stateless_csrf' => ['enabled' => true, 'form_types' => ['App\\Form\\A']]]);
        $container->prependExtensionConfig('nowo_form_kit', ['stateless_csrf' => ['token_id' => 'public_form']]);

        (new FormKitExtension())->prepend($container);

        $tokenIds = [];
        foreach ($container->getExtensionConfig('framework') as $config) {
            foreach ($config['csrf_protection']['stateless_token_ids'] ?? [] as $id) {
                $tokenIds[] = $id;
            }
        }
        self::assertSame(['public_form'], $tokenIds);
    }

    public function testPrependSkipsStatelessTokenIdWhenDisabledOrOptedOut(): void
    {
        foreach ([
            [],
            ['stateless_csrf' => ['enabled' => false]],
            ['stateless_csrf' => ['enabled' => true, 'register_stateless_token_id' => false]],
            ['stateless_csrf' => ['enabled' => true, 'token_id' => '']],
        ] as $raw) {
            $container = new ContainerBuilder();
            $container->registerExtension(new FrameworkExtension());
            $container->prependExtensionConfig('nowo_form_kit', $raw);

            (new FormKitExtension())->prepend($container);

            foreach ($container->getExtensionConfig('framework') as $config) {
                self::assertArrayNotHasKey('csrf_protection', $config);
            }
        }
    }

    public function testPrependSkipsWhenFrameworkExtensionIsMissing(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->prepend($container);

        self::assertFalse($container->hasExtension('framework'));
        self::assertSame([], $container->getExtensionConfig('framework'));
    }
}
