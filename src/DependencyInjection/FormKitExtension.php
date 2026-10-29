<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use Nowo\FormKitBundle\Form\Extension\UnlabelledChoiceAriaLabelExtension;
use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use Nowo\FormKitBundle\Twig\CsrfActionFormExtension;
use Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;

use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Loads FormKitBundle configuration and services.
 *
 * Legacy root-level keys and YAML keys "default_config" / "configs" are normalized
 * in {@see Configuration} (beforeNormalization) into "default_profile" / "profiles".
 * During transition both new and legacy container parameters are set.
 *
 * Registers the {@see Configuration::ALIAS} Symfony asset package for files under
 * `src/Resources/public/` published by `assets:install` to `/bundles/nowoformkit/`.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class FormKitExtension extends Extension implements PrependExtensionInterface
{
    public const LOCALE_TABS_FORM_THEME = '@NowoFormKitBundle/form/locale_tabs_theme.html.twig';

    /**
     * Registers the bundle asset package before FrameworkExtension processes assets.
     *
     * Load published files with {@code asset('help-modal.js', 'nowo_form_kit')} (and CSS).
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('framework')) {
            return;
        }

        $this->prependStatelessCsrfTokenId($container);
        $this->prependLocaleTabsFormTheme($container);

        $container->prependExtensionConfig('framework', [
            'assets' => [
                'packages' => [
                    Configuration::ALIAS => [
                        'base_path' => '/bundles/nowoformkit',
                    ],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $profilesMap = $config['profiles'];
        if ($profilesMap === []) {
            $profilesMap = [
                Configuration::DEFAULT_PROFILE_NAME => [
                    'alias'                         => Configuration::DEFAULT_PROFILE_NAME,
                    'translation_domain'            => $config['translation_domain'],
                    'auto_placeholder'              => $config['auto_placeholder'] ?? true,
                    'auto_help'                     => $config['auto_help'] ?? true,
                    'required_label_suffix'         => $config['required_label_suffix'] ?? null,
                    'help_modal'                    => $config['help_modal'] ?? [],
                    'defaults'                      => $config['defaults'],
                    'field_types'                   => $config['field_types'],
                    'constraint_message_convention' => $config['constraint_message_convention'] ?? false,
                    'by_form'                       => $config['by_form'] ?? [],
                ],
            ];
        }

        $requireTranslatedDefault = (bool) ($config['convention_requires_translation'] ?? false);

        $normalized = [];
        foreach ($profilesMap as $name => $c) {
            $normalized[$name] = [
                'translation_domain'              => $c['translation_domain'],
                'auto_placeholder'                => (bool) ($c['auto_placeholder'] ?? true),
                'auto_help'                       => (bool) ($c['auto_help'] ?? true),
                'convention_requires_translation' => (bool) ($c['convention_requires_translation'] ?? $requireTranslatedDefault),
                'required_label_suffix'           => $c['required_label_suffix'] ?? null,
                'help_modal'                      => $c['help_modal'] ?? [],
                'defaults'                        => $this->withProfileHelpAttr($c['defaults'], $c['help_attr'] ?? []),
                'field_types'                     => $c['field_types'],
                'constraint_message_convention'   => (bool) ($c['constraint_message_convention'] ?? false),
                'by_form'                         => $c['by_form'] ?? [],
                'css_framework'                   => $c['css_framework'] ?? null,
            ];
        }

        $normalized['filter'] ??= $this->builtInFilterProfile($requireTranslatedDefault);

        $defaultProfile = $config['default_profile'];
        if (!isset($normalized[$defaultProfile])) {
            throw new InvalidArgumentException(sprintf('nowo_form_kit.default_profile "%s" must be a key in nowo_form_kit.profiles. Available: %s.', $defaultProfile, implode(', ', array_keys($normalized))));
        }

        $container->setParameter('nowo_form_kit.profiles', $normalized);
        $container->setParameter('nowo_form_kit.default_profile', $defaultProfile);
        // BC: legacy parameter names (same values)
        $container->setParameter('nowo_form_kit.configs', $normalized);
        $container->setParameter('nowo_form_kit.default_config', $defaultProfile);
        $container->setParameter('nowo_form_kit.type_map', $config['type_map'] ?? []);
        $container->setParameter('nowo_form_kit.css_framework', $config['css_framework'] ?? 'bootstrap');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        /** @var array{enabled: bool, token_id: string, form_types: list<string>, register_stateless_token_id: bool} $statelessCsrf */
        $statelessCsrf = $config['stateless_csrf'];
        $this->registerStatelessCsrf($container, $statelessCsrf);

        /** @var array{unlabelled_choice_aria_label: bool} $accessibility */
        $accessibility = $config['accessibility'];
        $this->registerAccessibility($container, $accessibility);

        /** @var array{csrf_action_form: bool} $twig */
        $twig = $config['twig'];
        $this->registerTwig($container, $twig);

        /** @var array{locales: list<string>, default_locale: string|null, renderer: string, form_theme: bool} $localeTabs */
        $localeTabs = $config['locale_tabs'];
        $this->registerLocaleTabs($container, $localeTabs);
    }

    /**
     * Locale tabs (2.13): parameters for {@see TranslatableMapType} and the {@see LocaleTabsTwigExtension}
     * (autoconfigured as twig.extension). Empty locales / null default locale are filled from
     * framework.enabled_locales / default_locale by LocaleTabsLocalesPass.
     *
     * @param array{locales: list<string>, default_locale: string|null, renderer: string, form_theme: bool} $localeTabs
     */
    private function registerLocaleTabs(ContainerBuilder $container, array $localeTabs): void
    {
        $container->setParameter('nowo_form_kit.locale_tabs.locales', $localeTabs['locales']);
        $container->setParameter('nowo_form_kit.locale_tabs.default_locale', $localeTabs['default_locale']);
        $container->setParameter('nowo_form_kit.locale_tabs.renderer', $localeTabs['renderer']);

        // twig/twig is a hard dependency (through twig/extra-bundle)

        $container->register(LocaleTabsTwigExtension::class, LocaleTabsTwigExtension::class)
            ->setArguments([
                '%nowo_form_kit.locale_tabs.locales%',
                '%nowo_form_kit.locale_tabs.default_locale%',
                $localeTabs['renderer'] === 'native' ? false : null,
            ])
            // REQ-SF-003: tagged twig.extension by TwigBundle autoconfiguration (Twig ExtensionInterface), no manual tag.
            ->setAutoconfigured(true)
            ->setPublic(false);
    }

    /**
     * Registers the locale tabs form theme (prepended, so host themes listed later still win) unless
     * `nowo_form_kit.locale_tabs.form_theme` is false in any config file.
     */
    private function prependLocaleTabsFormTheme(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }
        foreach ($container->getExtensionConfig(Configuration::ALIAS) as $config) {
            if (is_array($config['locale_tabs'] ?? null) && ($config['locale_tabs']['form_theme'] ?? true) === false) {
                return;
            }
        }

        $container->prependExtensionConfig('twig', ['form_themes' => [self::LOCALE_TABS_FORM_THEME]]);
    }

    /**
     * Registers {@see CsrfActionFormExtension} when Twig is installed and
     * `twig.csrf_action_form` is enabled (default).
     *
     * @param array{csrf_action_form: bool} $twig
     */
    private function registerTwig(ContainerBuilder $container, array $twig): void
    {
        $enabled = $twig['csrf_action_form'] && class_exists(AbstractExtension::class);
        $container->setParameter('nowo_form_kit.twig.csrf_action_form', $enabled);

        if (!$enabled) {
            return;
        }

        $container->register(CsrfActionFormExtension::class, CsrfActionFormExtension::class)
            ->setArguments([new Reference(CsrfOnlyFormFactory::class)])
            // REQ-SF-003: tagged twig.extension by TwigBundle autoconfiguration (Twig ExtensionInterface), no manual tag.
            ->setAutoconfigured(true)
            ->setPublic(false);
    }

    /**
     * Registers {@see UnlabelledChoiceAriaLabelExtension} only when
     * `accessibility.unlabelled_choice_aria_label` is enabled (default), so disabling it costs nothing.
     *
     * @param array{unlabelled_choice_aria_label: bool} $accessibility
     */
    private function registerAccessibility(ContainerBuilder $container, array $accessibility): void
    {
        $enabled = $accessibility['unlabelled_choice_aria_label'];
        $container->setParameter('nowo_form_kit.accessibility.unlabelled_choice_aria_label', $enabled);

        if (!$enabled) {
            return;
        }

        $container->register(UnlabelledChoiceAriaLabelExtension::class, UnlabelledChoiceAriaLabelExtension::class)
            ->setArguments([new Reference(TranslatorInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE)])
            // REQ-SF-003: tagged form.type_extension by FrameworkBundle autoconfiguration (getExtendedTypes()), no manual tag.
            ->setAutoconfigured(true)
            ->setPublic(false);
    }

    /**
     * One {@see StatelessCsrfTokenIdExtension} service per configured form type (tag attribute
     * `extended_type`), so the static getExtendedTypes() contract does not limit the list.
     *
     * @param array{enabled: bool, token_id: string, form_types: list<string>, register_stateless_token_id: bool} $statelessCsrf
     */
    private function registerStatelessCsrf(ContainerBuilder $container, array $statelessCsrf): void
    {
        $formTypes = $statelessCsrf['enabled'] ? array_values(array_unique($statelessCsrf['form_types'])) : [];
        $container->setParameter('nowo_form_kit.stateless_csrf.enabled', $statelessCsrf['enabled']);
        $container->setParameter('nowo_form_kit.stateless_csrf.token_id', $statelessCsrf['token_id']);
        $container->setParameter('nowo_form_kit.stateless_csrf.form_types', $formTypes);

        foreach ($formTypes as $index => $formType) {
            // Explicit tag on purpose (REQ-SF-003 rule 4): the extended type comes from configuration, one service per
            // listed type, which neither an attribute nor autoconfiguration (static getExtendedTypes()) can express.
            $container->register('nowo_form_kit.stateless_csrf_extension.' . $index, StatelessCsrfTokenIdExtension::class)
                ->setArguments([$statelessCsrf['token_id']])
                ->addTag('form.type_extension', ['extended_type' => $formType])
                ->setPublic(false);
        }
    }

    /**
     * When enabled (and `register_stateless_token_id` is true), adds the token id to
     * `framework.csrf_protection.stateless_token_ids` so hosts do not have to list it twice.
     */
    private function prependStatelessCsrfTokenId(ContainerBuilder $container): void
    {
        $statelessCsrf = null;
        foreach ($container->getExtensionConfig($this->getAlias()) as $raw) {
            if (isset($raw['stateless_csrf']) && is_array($raw['stateless_csrf'])) {
                $statelessCsrf = array_merge($statelessCsrf ?? [], $raw['stateless_csrf']);
            }
        }

        if ($statelessCsrf === null || !($statelessCsrf['enabled'] ?? false) || !($statelessCsrf['register_stateless_token_id'] ?? true)) {
            return;
        }

        $tokenId = $statelessCsrf['token_id'] ?? Configuration::DEFAULT_STATELESS_CSRF_TOKEN_ID;
        if (!is_string($tokenId) || $tokenId === '') {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'csrf_protection' => [
                'stateless_token_ids' => [$tokenId],
            ],
        ]);
    }

    /**
     * Built-in profile for {@see AbstractGetFilterType}.
     *
     * @return array<string, mixed>
     */
    private function builtInFilterProfile(bool $conventionRequiresTranslation): array
    {
        return [
            'translation_domain'              => 'messages',
            'auto_placeholder'                => true,
            'auto_help'                       => true,
            'convention_requires_translation' => $conventionRequiresTranslation,
            'required_label_suffix'           => null,
            'help_modal'                      => [],
            'defaults'                        => [
                'label'     => false,
                'required'  => false,
                'attr'      => [],
                'row_attr'  => [],
                'help_attr' => [],
            ],
            'field_types'                   => [],
            'constraint_message_convention' => false,
            'by_form'                       => [],
            'css_framework'                 => null,
        ];
    }

    /**
     * Profile-level `help_attr` (2.13) under `defaults.help_attr` (the latter wins per attribute).
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $helpAttr
     *
     * @return array<string, mixed>
     */
    private function withProfileHelpAttr(array $defaults, array $helpAttr): array
    {
        if ($helpAttr === []) {
            return $defaults;
        }
        $own                   = is_array($defaults['help_attr'] ?? null) ? $defaults['help_attr'] : [];
        $defaults['help_attr'] = array_merge($helpAttr, $own);

        return $defaults;
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
