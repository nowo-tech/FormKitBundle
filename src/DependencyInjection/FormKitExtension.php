<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use function sprintf;

/**
 * Loads FormKitBundle configuration and services.
 *
 * Normalizes config: if "configs" is empty, builds a single "default" config from root-level
 * translation_domain/defaults/field_types for backward compatibility.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class FormKitExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $configsMap = $config['configs'];
        if ($configsMap === []) {
            $configsMap = [
                Configuration::DEFAULT_CONFIG_NAME => [
                    'alias'                 => Configuration::DEFAULT_CONFIG_NAME,
                    'translation_domain'    => $config['translation_domain'],
                    'required_label_suffix' => $config['required_label_suffix'] ?? null,
                    'defaults'              => $config['defaults'],
                    'field_types'           => $config['field_types'],
                ],
            ];
        }

        $normalized = [];
        foreach ($configsMap as $name => $c) {
            $normalized[$name] = [
                'translation_domain'    => $c['translation_domain'],
                'required_label_suffix' => $c['required_label_suffix'] ?? null,
                'defaults'              => $c['defaults'],
                'field_types'           => $c['field_types'],
            ];
        }

        $defaultConfig = $config['default_config'];
        if (!isset($normalized[$defaultConfig])) {
            throw new InvalidArgumentException(sprintf('nowo_form_kit.default_config "%s" must be a key in nowo_form_kit.configs. Available: %s.', $defaultConfig, implode(', ', array_keys($normalized))));
        }

        $container->setParameter('nowo_form_kit.configs', $normalized);
        $container->setParameter('nowo_form_kit.default_config', $defaultConfig);
        $container->setParameter('nowo_form_kit.type_map', $config['type_map'] ?? []);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
