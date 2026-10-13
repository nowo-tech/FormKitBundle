<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use Nowo\FormKitBundle\Form\Extension\UnlabelledChoiceAriaLabelExtension;
use Nowo\FormKitBundle\Twig\CsrfActionFormExtension;
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
                'defaults'                        => $c['defaults'],
                'field_types'                     => $c['field_types'],
                'constraint_message_convention'   => (bool) ($c['constraint_message_convention'] ?? false),
                'by_form'                         => $c['by_form'] ?? [],
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
            ->addTag('twig.extension')
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
            ->addTag('form.type_extension')
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
        ];
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
