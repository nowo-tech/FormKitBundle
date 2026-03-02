<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Merges form field options in cascade: global defaults → field type → form-level → field options.
 *
 * Supports multiple coexisting configs; resolve() accepts an optional config name (otherwise default_config is used).
 * Applies convention: label, placeholder and help default to translation keys
 * "form_snake.field_snake.label", ".placeholder", ".help" unless explicitly set to false.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormOptionsMerger
{
    /**
     * @var array<string, array{translation_domain: string, required_label_suffix?: string|null, defaults: array{attr: array, row_attr: array}, field_types: array<string, mixed>}>
     */
    private array $configs;

    private string $defaultConfigName;

    /**
     * @param array<string, array{translation_domain: string, defaults: array{attr: array, row_attr: array}, field_types: array}> $configs
     */
    public function __construct(array $configs, string $defaultConfigName)
    {
        $this->configs = $configs;
        $this->defaultConfigName = $defaultConfigName;
    }

    /**
     * Resolves final options for a form field with cascading merge and convention-based keys.
     *
     * @param string|null           $configName Config name (key in configs); when null, default_config is used
     * @param array<string, mixed>  $options    Field-specific options (override convention and defaults)
     *
     * @return array<string, mixed> Merged options ready for FormBuilder::add()
     */
    public function resolve(
        string $formName,
        string $fieldName,
        string $type,
        array $options = [],
        ?string $configName = null
    ): array {
        $name = $configName ?? $this->defaultConfigName;
        if (!isset($this->configs[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown form kit config "%s". Available: %s.', $name, implode(', ', array_keys($this->configs))));
        }
        $config = $this->configs[$name];
        $translationDomain = $config['translation_domain'];
        $defaults = $config['defaults'];
        $fieldTypes = $config['field_types'];

        $fieldNameSnake = $this->camelCaseToSnakeCase($fieldName);
        $baseKey = $formName . '.' . $fieldNameSnake;

        $base = [
            'translation_domain' => $translationDomain,
            'label' => $baseKey . '.label',
            'help' => $baseKey . '.help',
            'attr' => array_merge(
                ['placeholder' => $baseKey . '.placeholder'],
                $defaults['attr'] ?? []
            ),
            'row_attr' => $defaults['row_attr'] ?? [],
        ];

        $typeShortName = $this->typeToShortName($type);
        $typeDefaults = $fieldTypes[$typeShortName] ?? $fieldTypes[$type] ?? [];

        $merged = $this->arrayReplaceRecursive($base, $typeDefaults);
        $merged = $this->arrayReplaceRecursive($merged, $options);
        $merged = $this->normalizePlaceholderToAttr($merged, $options);

        return $this->removeExplicitFalseConventionKeys($merged, $options);
    }

    /**
     * Converts a form type FQCN to short name for config lookup (e.g. TextType -> text).
     */
    private function typeToShortName(string $type): string
    {
        if (str_contains($type, '\\')) {
            $short = (string) preg_replace('/Type$/', '', substr($type, strrpos($type, '\\') + 1));

            return $this->camelCaseToSnakeCase($short);
        }

        return $type;
    }

    private function camelCaseToSnakeCase(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_\\0', lcfirst($name)));
    }

    /**
     * Recursive replace: nested arrays are merged, scalars are replaced.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $replace
     *
     * @return array<string, mixed>
     */
    private function arrayReplaceRecursive(array $base, array $replace): array
    {
        foreach ($replace as $k => $v) {
            if (\is_array($v) && isset($base[$k]) && \is_array($base[$k])) {
                $base[$k] = $this->arrayReplaceRecursive($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }

        return $base;
    }

    /**
     * Normalizes legacy/custom "placeholder" root option to attr.placeholder.
     * This keeps backward compatibility and avoids invalid options on types like TextType.
     *
     * @param array<string, mixed> $merged
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function normalizePlaceholderToAttr(array $merged, array $options): array
    {
        $hasExplicitPlaceholder = array_key_exists('placeholder', $options);
        $explicitPlaceholder = $hasExplicitPlaceholder ? $options['placeholder'] : null;
        $placeholder = $merged['placeholder'] ?? null;

        unset($merged['placeholder']);

        if ($hasExplicitPlaceholder && $explicitPlaceholder === false) {
            if (isset($merged['attr']) && \is_array($merged['attr'])) {
                unset($merged['attr']['placeholder']);
            }

            return $merged;
        }

        $placeholderToApply = $hasExplicitPlaceholder ? $explicitPlaceholder : $placeholder;
        if ($placeholderToApply !== null && $placeholderToApply !== false) {
            $merged['attr'] = (isset($merged['attr']) && \is_array($merged['attr'])) ? $merged['attr'] : [];
            if (!array_key_exists('placeholder', $merged['attr'])) {
                $merged['attr']['placeholder'] = $placeholderToApply;
            }
        }

        return $merged;
    }

    /**
     * If user passed label => false or help => false, remove those keys
     * so the form component does not use the convention key.
     *
     * @param array<string, mixed> $merged
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function removeExplicitFalseConventionKeys(array $merged, array $options): array
    {
        foreach (['label', 'help'] as $key) {
            if (array_key_exists($key, $options) && $options[$key] === false) {
                unset($merged[$key]);
            }
        }

        return $merged;
    }
}
