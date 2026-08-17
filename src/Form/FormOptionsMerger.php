<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;

use function array_key_exists;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Merges form field options in cascade:
 * global defaults → field type → by_form defaults → by_form field → field options.
 *
 * Supports multiple coexisting profiles; resolve() accepts an optional profile name (otherwise default_profile is used).
 * Applies convention: label, placeholder and help default to translation keys
 * "form_snake.field_snake.label", ".placeholder", ".help" unless explicitly set to false.
 *
 * Optional constraint_message_convention: constraints without an explicit message get
 * "{form}.{field}.constraints.{ConstraintName}" (validators catalog).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormOptionsMerger
{
    /** @var list<string> */
    private const SCALAR_DEFAULT_KEYS = ['label', 'placeholder', 'help', 'required'];

    /**
     * @param array<string, array{
     *     translation_domain: string,
     *     auto_placeholder?: bool,
     *     auto_help?: bool,
     *     defaults: array{
     *         attr: array<string, mixed>,
     *         row_attr: array<string, mixed>,
     *         help_attr?: array<string, mixed>,
     *         label?: mixed,
     *         placeholder?: mixed,
     *         help?: mixed,
     *         required?: bool
     *     },
     *     field_types: array<string, array<string, mixed>>,
     *     constraint_message_convention?: bool,
     *     by_form?: array<string, array{
     *         defaults?: array{
     *             attr?: array<string, mixed>,
     *             row_attr?: array<string, mixed>,
     *             help_attr?: array<string, mixed>,
     *             label?: mixed,
     *             placeholder?: mixed,
     *             help?: mixed,
     *             required?: bool
     *         },
     *         fields?: array<string, array<string, mixed>>
     *     }>
     * }> $profiles
     */
    public function __construct(
        private array $profiles,
        private readonly string $defaultProfileName,
        private readonly ConstraintDefinitionFactory $constraintDefinitionFactory,
    ) {
    }

    /**
     * Resolves final options for a form field with cascading merge and convention-based keys.
     *
     * @param string|null $configName Profile name (key in profiles); when null, default_profile is used
     * @param array<string, mixed> $options Field-specific options (override convention and defaults)
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
        $name = $configName ?? $this->defaultProfileName;
        if (!isset($this->profiles[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown form kit profile "%s". Available: %s.', $name, implode(', ', array_keys($this->profiles))));
        }
        $config            = $this->profiles[$name];
        $translationDomain = $config['translation_domain'];
        $defaults          = $config['defaults'];
        $fieldTypes        = $config['field_types'];
        $byFormMap         = $config['by_form'] ?? [];
        $messageConvention = (bool) ($config['constraint_message_convention'] ?? false);
        $autoPlaceholder   = (bool) ($config['auto_placeholder'] ?? true);
        $autoHelp          = (bool) ($config['auto_help'] ?? true);

        $fieldNameSnake = $this->camelCaseToSnakeCase($fieldName);
        $baseKey        = $formName . '.' . $fieldNameSnake;

        $baseAttr = $defaults['attr'];
        if ($autoPlaceholder) {
            $baseAttr = array_merge(['placeholder' => $baseKey . '.placeholder'], $baseAttr);
        }

        $base = [
            'translation_domain' => $translationDomain,
            'label'              => $baseKey . '.label',
            'attr'               => $baseAttr,
            'row_attr'           => $defaults['row_attr'],
            'help_attr'          => $defaults['help_attr'] ?? [],
        ];
        if ($autoHelp) {
            $base['help'] = $baseKey . '.help';
        }
        $base = $this->applyScalarDefaults($base, $defaults);

        $typeShortName = $this->typeToShortName($type);
        $typeDefaults  = $fieldTypes[$typeShortName] ?? $fieldTypes[$type] ?? [];

        $typeConstraintDefs = [];
        if (isset($typeDefaults['constraints']) && is_array($typeDefaults['constraints'])) {
            $typeConstraintDefs = $typeDefaults['constraints'];
        }
        unset($typeDefaults['constraints']);

        $formDefaults       = [];
        $formField          = [];
        $formConstraintDefs = [];
        if (isset($byFormMap[$formName])) {
            $formEntry    = $byFormMap[$formName];
            $formDefaults = [
                'attr'      => $formEntry['defaults']['attr'] ?? [],
                'row_attr'  => $formEntry['defaults']['row_attr'] ?? [],
                'help_attr' => $formEntry['defaults']['help_attr'] ?? [],
            ];
            $formDefaults = $this->applyScalarDefaults($formDefaults, $formEntry['defaults'] ?? []);
            $fields       = $formEntry['fields'] ?? [];
            if (isset($fields[$fieldName])) {
                $formField = $fields[$fieldName];
            } elseif (isset($fields[$fieldNameSnake])) {
                $formField = $fields[$fieldNameSnake];
            }
            if (isset($formField['constraints']) && is_array($formField['constraints'])) {
                $formConstraintDefs = $formField['constraints'];
            }
            unset($formField['constraints']);
        }

        $optionsConstraints = array_key_exists('constraints', $options) && is_array($options['constraints'])
            ? $options['constraints']
            : [];
        $optionsForMerge = $options;
        unset($optionsForMerge['constraints']);

        $merged = $this->arrayReplaceRecursive($base, $typeDefaults);
        $merged = $this->arrayReplaceRecursive($merged, $formDefaults);
        $merged = $this->arrayReplaceRecursive($merged, $formField);
        $merged = $this->arrayReplaceRecursive($merged, $optionsForMerge);
        $merged = $this->normalizePlaceholderToAttr($merged, $options);

        $merged = $this->removeExplicitFalseConventionKeys($merged, $options);

        $constraintDefs = array_values(array_merge($typeConstraintDefs, $formConstraintDefs, $optionsConstraints));
        if ($constraintDefs !== []) {
            $messagePrefix         = $messageConvention ? $baseKey : null;
            $merged['constraints'] = $this->constraintDefinitionFactory->create($constraintDefs, $messagePrefix);
        }

        return $this->stripButtonIncompatibleOptions($merged, $typeShortName);
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
            $base[$k] = is_array($v) && isset($base[$k]) && is_array($base[$k]) ? $this->arrayReplaceRecursive($base[$k], $v) : $v;
        }

        return $base;
    }

    /**
     * Copies optional scalar defaults (label / placeholder / help / required) onto a merge layer.
     *
     * @param array<string, mixed> $target
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function applyScalarDefaults(array $target, array $source): array
    {
        foreach (self::SCALAR_DEFAULT_KEYS as $key) {
            if (array_key_exists($key, $source)) {
                $target[$key] = $source[$key];
            }
        }

        return $target;
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
        $explicitPlaceholder    = $hasExplicitPlaceholder ? $options['placeholder'] : null;
        $placeholder            = $merged['placeholder'] ?? null;

        unset($merged['placeholder']);

        $placeholderToApply = $hasExplicitPlaceholder ? $explicitPlaceholder : $placeholder;

        // false from PHP options, profile defaults, or field_types clears attr.placeholder.
        if ($placeholderToApply === false) {
            if (isset($merged['attr']) && is_array($merged['attr'])) {
                unset($merged['attr']['placeholder']);
            }

            return $merged;
        }

        if ($placeholderToApply !== null) {
            $merged['attr'] = (isset($merged['attr']) && is_array($merged['attr'])) ? $merged['attr'] : [];
            if (!array_key_exists('placeholder', $merged['attr'])) {
                $merged['attr']['placeholder'] = $placeholderToApply;
            }
        }

        return $merged;
    }

    /**
     * Button/Submit/Reset types do not accept FormType help options (help, help_attr, …).
     * Drop FormKit defaults that would make OptionsResolver throw.
     *
     * @param array<string, mixed> $merged
     *
     * @return array<string, mixed>
     */
    private function stripButtonIncompatibleOptions(array $merged, string $typeShortName): array
    {
        if (!in_array($typeShortName, ['button', 'submit', 'reset'], true)) {
            return $merged;
        }

        foreach ([
            'help',
            'help_attr',
            'help_html',
            'help_translation_parameters',
            'error_bubbling',
            'required',
            'constraints',
        ] as $key) {
            unset($merged[$key]);
        }

        if (isset($merged['attr']) && is_array($merged['attr'])) {
            unset($merged['attr']['placeholder']);
        }

        return $merged;
    }

    /**
     * If user passed label => false or help => false in field options, remove those keys
     * so the form component does not use the convention key.
     *
     * Profile / field_types {@code label: false} is left as boolean false (Symfony suppresses the label).
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
