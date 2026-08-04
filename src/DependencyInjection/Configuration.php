<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for FormKitBundle.
 *
 * Supports multiple coexisting profiles under "profiles" (each with alias, translation_domain,
 * required_label_suffix, help_modal, defaults, field_types, by_form, constraint_message_convention).
 * One profile is chosen as default via "default_profile".
 * Legacy root-level keys are normalized into a single "default" profile when "profiles" is not set.
 * Legacy YAML keys "default_config" / "configs" are accepted via beforeNormalization.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Configuration implements ConfigurationInterface
{
    public const ALIAS = 'nowo_form_kit';

    public const DEFAULT_PROFILE_NAME = 'default';

    /** @deprecated Use {@see DEFAULT_PROFILE_NAME} instead. */
    public const DEFAULT_CONFIG_NAME = self::DEFAULT_PROFILE_NAME;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $root        = $treeBuilder->getRootNode();

        $root
            ->beforeNormalization()
                ->always()
                ->then(static function (?array $config): array {
                    $config ??= [];

                    // BC: default_config → default_profile
                    if (!isset($config['default_profile']) && isset($config['default_config'])) {
                        $config['default_profile'] = $config['default_config'];
                        unset($config['default_config']);
                    }

                    // BC: configs → profiles
                    if (!isset($config['profiles']) && isset($config['configs'])) {
                        $config['profiles'] = $config['configs'];
                        unset($config['configs']);
                    }

                    // BC: legacy root-level keys → profiles.default
                    if (!isset($config['profiles']) || $config['profiles'] === []) {
                        $config['profiles'] = [
                            self::DEFAULT_PROFILE_NAME => [
                                'alias'                         => self::DEFAULT_PROFILE_NAME,
                                'translation_domain'            => $config['translation_domain'] ?? 'messages',
                                'auto_placeholder'              => $config['auto_placeholder'] ?? true,
                                'auto_help'                     => $config['auto_help'] ?? true,
                                'required_label_suffix'         => $config['required_label_suffix'] ?? null,
                                'help_modal'                    => $config['help_modal'] ?? [],
                                'defaults'                      => $config['defaults'] ?? ['attr' => [], 'row_attr' => []],
                                'field_types'                   => $config['field_types'] ?? [],
                                'constraint_message_convention' => $config['constraint_message_convention'] ?? false,
                                'by_form'                       => $config['by_form'] ?? [],
                            ],
                        ];
                        unset(
                            $config['translation_domain'],
                            $config['auto_placeholder'],
                            $config['auto_help'],
                            $config['required_label_suffix'],
                            $config['help_modal'],
                            $config['defaults'],
                            $config['field_types'],
                            $config['constraint_message_convention'],
                            $config['by_form'],
                        );
                    }

                    if (!isset($config['default_profile'])) {
                        $profileNames              = array_keys($config['profiles']);
                        $config['default_profile'] = $profileNames[0] ?? self::DEFAULT_PROFILE_NAME;
                    }

                    return $config;
                })
            ->end()
            ->children()
                ->arrayNode('type_map')
                    ->info('Additional form type names (snake_case) => FQCN. Merged with built-in and optional UX types (e.g. dropzone when symfony/ux-dropzone is installed).')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('default_profile')
                    ->info('Name of the profile to use when no profile is specified (key in profiles)')
                    ->defaultValue(self::DEFAULT_PROFILE_NAME)
                ->end()
                ->scalarNode('css_framework')
                    ->info('CSS framework for CssClassUtilities (column merge + class ordering): bootstrap, tailwind, foundation, none.')
                    ->defaultValue('bootstrap')
                    ->validate()
                        ->ifNotInArray(['bootstrap', 'tailwind', 'foundation', 'none'])
                        ->thenInvalid('nowo_form_kit.css_framework must be one of: bootstrap, tailwind, foundation, none.')
                    ->end()
                ->end()
                ->arrayNode('profiles')
                    ->info('Named profiles; each has alias and form options. Use default_profile to choose the default.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('alias')
                                ->info('Alias for this profile (e.g. for reference in form types)')
                                ->isRequired()
                            ->end()
                            ->scalarNode('translation_domain')
                                ->defaultValue('messages')
                            ->end()
                            ->booleanNode('auto_placeholder')
                                ->info('When true (default), unset placeholders become {form}.{field}.placeholder translation keys. Set false for kits that only set explicit labels.')
                                ->defaultTrue()
                            ->end()
                            ->booleanNode('auto_help')
                                ->info('When true (default), unset help becomes {form}.{field}.help translation keys. Set false to avoid raw missing-help keys in the UI.')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('required_label_suffix')
                                ->info('Appended to the label when the field is required (e.g. " *"). Empty or null to disable.')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('help_modal')
                                ->info('Default help modal configuration (used when the field option "help_modal" is enabled).')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('framework')
                                        ->info('Modal framework to use when opening from frontend.')
                                        ->defaultValue('bootstrap5')
                                    ->end()
                                    ->scalarNode('icon_html')
                                        ->info('HTML snippet inserted next to the label to trigger the help modal (fallback when ux_icon is not used or UX Icons is unavailable).')
                                        ->defaultValue('<span class="nowo-help-modal-icon" aria-hidden="true">?</span>')
                                    ->end()
                                    ->scalarNode('ux_icon')
                                        ->info('Optional. Symfony UX Icons name (e.g. lucide:circle-help). Requires symfony/ux-icons; when set and IconRendererInterface is available, overrides icon_html.')
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('ux_icon_attributes')
                                        ->info('HTML attributes for renderIcon() when ux_icon is set (e.g. class: nowo-help-modal-icon).')
                                        ->defaultValue([])
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->scalarNode('trigger_class')
                                        ->info('CSS classes for the clickable trigger wrapper (after label text and required suffix). Default: circle button style.')
                                        ->defaultValue('nowo-help-modal-trigger nowo-help-modal-trigger--circle')
                                    ->end()
                                ->end()
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
                                        ->arrayNode('constraints')
                                            ->info('Symfony Validator constraints for this field type (short names like NotBlank, Email, or { NotBlank: { message: "..." } } per entry). See ConstraintDefinitionFactory.')
                                            ->defaultValue([])
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->booleanNode('constraint_message_convention')
                                ->info('When true, constraints without an explicit "message" get key {form_snake}.{field_snake}.constraints.{ConstraintName} (put translations in the validators catalog). Default: false.')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('by_form')
                                ->info('Per-form option defaults keyed by form name / block prefix (e.g. user_profile). Merged after field_types, before per-field options.')
                                ->defaultValue([])
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
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
                                        ->arrayNode('fields')
                                            ->info('Per-field overrides for this form (key = field name as used in add*()).')
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
                                                    ->arrayNode('constraints')
                                                        ->defaultValue([])
                                                        ->prototype('variable')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                // Legacy root-level (used when profiles is not set to build a single "default" profile)
                ->scalarNode('translation_domain')
                    ->info('(Legacy) Used when profiles is not set')
                    ->defaultValue('messages')
                ->end()
                ->scalarNode('required_label_suffix')
                    ->info('(Legacy) Suffix for required field labels when profiles is not set')
                    ->defaultNull()
                ->end()
                ->arrayNode('help_modal')
                    ->info('(Legacy) Default help modal configuration when profiles is not used.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('framework')
                            ->defaultValue('bootstrap5')
                        ->end()
                        ->scalarNode('icon_html')
                            ->defaultValue('<span class="nowo-help-modal-icon" aria-hidden="true">?</span>')
                        ->end()
                        ->scalarNode('ux_icon')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('ux_icon_attributes')
                            ->defaultValue([])
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('trigger_class')
                            ->defaultValue('nowo-help-modal-trigger nowo-help-modal-trigger--circle')
                        ->end()
                    ->end()
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
                            ->arrayNode('constraints')
                                ->defaultValue([])
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('constraint_message_convention')
                    ->info('(Legacy) Used when profiles is not set')
                    ->defaultFalse()
                ->end()
                ->arrayNode('by_form')
                    ->info('(Legacy) Per-form defaults when profiles is not set')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('defaults')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('attr')->defaultValue([])->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                                    ->arrayNode('row_attr')->defaultValue([])->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                                ->end()
                            ->end()
                            ->arrayNode('fields')
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
                                        ->arrayNode('constraints')
                                            ->defaultValue([])
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
