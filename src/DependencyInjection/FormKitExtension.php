<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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

        $normalized = [];
        foreach ($profilesMap as $name => $c) {
            $normalized[$name] = [
                'translation_domain'            => $c['translation_domain'],
                'auto_placeholder'              => (bool) ($c['auto_placeholder'] ?? true),
                'auto_help'                     => (bool) ($c['auto_help'] ?? true),
                'required_label_suffix'         => $c['required_label_suffix'] ?? null,
                'help_modal'                    => $c['help_modal'] ?? [],
                'defaults'                      => $c['defaults'],
                'field_types'                   => $c['field_types'],
                'constraint_message_convention' => (bool) ($c['constraint_message_convention'] ?? false),
                'by_form'                       => $c['by_form'] ?? [],
            ];
        }

        if (!isset($normalized['filter'])) {
            $normalized['filter'] = $this->builtInFilterProfile();
        }

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
    }

    /**
     * Built-in profile for {@see AbstractGetFilterType}.
     *
     * @return array<string, mixed>
     */
    private function builtInFilterProfile(): array
    {
        return [
            'translation_domain'    => 'messages',
            'auto_placeholder'      => true,
            'auto_help'             => true,
            'required_label_suffix' => null,
            'help_modal'            => [],
            'defaults'              => [
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
