<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for FormKitBundle.
 *
 * Supports multiple coexisting configs under "configs" (each with alias, translation_domain,
 * defaults, field_types). One config is chosen as default via "default_config".
 * Legacy root-level translation_domain/defaults/field_types are normalized into a single "default" config when configs are not set.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_form_kit';

    public const DEFAULT_CONFIG_NAME = 'default';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('type_map')
                    ->info('Additional form type names (snake_case) => FQCN. Merged with built-in and optional UX types (e.g. dropzone when symfony/ux-dropzone is installed).')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('default_config')
                    ->info('Name of the config to use when no config is specified (key in configs)')
                    ->defaultValue(self::DEFAULT_CONFIG_NAME)
                ->end()
                ->arrayNode('configs')
                    ->info('Named configs; each has alias and form options. Use default_config to choose the default.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('alias')
                                ->info('Alias for this config (e.g. for reference in form types)')
                                ->isRequired()
                            ->end()
                            ->scalarNode('translation_domain')
                                ->defaultValue('messages')
                            ->end()
                            ->scalarNode('required_label_suffix')
                                ->info('Appended to the label when the field is required (e.g. " *"). Empty or null to disable.')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('defaults')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('attr')
                                        ->defaultValue([])
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('row_attr')
                                        ->defaultValue([])
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('field_types')
                                ->defaultValue([])
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->arrayNode('attr')->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                                        ->arrayNode('row_attr')->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                                        ->scalarNode('label')->end()
                                        ->scalarNode('placeholder')->end()
                                        ->scalarNode('help')->end()
                                        ->scalarNode('translation_domain')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                // Legacy root-level (used when configs is not set to build a single "default" config)
                ->scalarNode('translation_domain')
                    ->info('(Legacy) Used when configs is not set')
                    ->defaultValue('messages')
                ->end()
                ->scalarNode('required_label_suffix')
                    ->info('(Legacy) Suffix for required field labels when configs is not set')
                    ->defaultNull()
                ->end()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attr')->defaultValue([])->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                        ->arrayNode('row_attr')->defaultValue([])->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                    ->end()
                ->end()
                ->arrayNode('field_types')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('attr')->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                            ->arrayNode('row_attr')->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                            ->scalarNode('label')->end()
                            ->scalarNode('placeholder')->end()
                            ->scalarNode('help')->end()
                            ->scalarNode('translation_domain')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
