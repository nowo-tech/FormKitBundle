<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormRegistryInterface;

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
 * Any explicit or configured placeholder wins over the convention key. ChoiceType (and subtypes such
 * as EntityType) keep the root "placeholder" as the empty-option label (false = no empty option) and
 * never get the convention key; checkbox / radio / hidden fields never get it either.
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

    /** Text-like field: root {@code placeholder} is moved to {@code attr.placeholder}; convention key applies. */
    private const ROLE_INPUT = 'input';

    /** ChoiceType or subtype (EntityType, EnumType, CountryType…): root {@code placeholder} is the empty-option label. */
    private const ROLE_CHOICE = 'choice';

    /** Checkbox / radio / hidden: no HTML placeholder, so no convention key. */
    private const ROLE_NO_PLACEHOLDER = 'no_placeholder';

    /**
     * ChoiceType subtypes recognised without a form registry (short names and FQCNs).
     *
     * @var list<string>
     */
    private const CHOICE_TYPES = [
        'choice', 'entity', 'enum', 'country', 'currency', 'language', 'locale', 'timezone',
        ChoiceType::class, EnumType::class, CountryType::class, CurrencyType::class,
        LanguageType::class, LocaleType::class, TimezoneType::class,
        'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType',
    ];

    /**
     * Types (and their subtypes) that never render an HTML placeholder.
     *
     * @var list<string>
     */
    private const NO_PLACEHOLDER_TYPES = [
        'checkbox', 'radio', 'hidden',
        CheckboxType::class, RadioType::class, HiddenType::class,
    ];

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
        private readonly ?FormRegistryInterface $formRegistry = null,
        private readonly ?FormTypeMap $formTypeMap = null,
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

        $base = [
            'translation_domain' => $translationDomain,
            'label'              => $baseKey . '.label',
            'attr'               => $defaults['attr'],
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

        $merged             = $this->arrayReplaceRecursive($base, $typeDefaults);
        $merged             = $this->arrayReplaceRecursive($merged, $formDefaults);
        $merged             = $this->arrayReplaceRecursive($merged, $formField);
        $merged             = $this->arrayReplaceRecursive($merged, $optionsForMerge);
        $role               = $this->placeholderRole($type);
        $hasRootPlaceholder = ($merged['placeholder'] ?? null) !== null;
        $merged             = $role === self::ROLE_CHOICE
            ? $this->normalizeChoicePlaceholder($merged, [$optionsForMerge, $formField, $typeDefaults])
            : $this->normalizePlaceholderToAttr($merged, $options);
        if ($autoPlaceholder && $role === self::ROLE_INPUT && !$hasRootPlaceholder && !$this->hasAttrPlaceholder($merged)) {
            // Convention key only when no layer set (or disabled) a placeholder; keep it first in attr.
            $merged['attr'] = ['placeholder' => $baseKey . '.placeholder'] + $this->attrOf($merged);
        }

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
     * Normalizes the root "placeholder" option to attr.placeholder (text-like types).
     * This keeps backward compatibility and avoids invalid options on types like TextType.
     *
     * Precedence (first match wins): explicit {@code placeholder: false} (clears) → explicit
     * {@code attr.placeholder} → explicit root {@code placeholder} → configured root {@code false}
     * (clears) → configured {@code attr.placeholder} → configured root {@code placeholder}.
     * The convention key is added afterwards only when nothing set one ({@see applyConventionPlaceholder()}).
     *
     * @param array<string, mixed> $merged
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function normalizePlaceholderToAttr(array $merged, array $options): array
    {
        $placeholder = $merged['placeholder'] ?? null;
        unset($merged['placeholder']);

        $explicit = array_key_exists('placeholder', $options) ? $options['placeholder'] : null;
        if ($explicit !== null) {
            if ($explicit === false) {
                return $this->unsetAttrPlaceholder($merged);
            }
            if (!$this->hasAttrPlaceholder($options)) {
                $merged['attr']                = $this->attrOf($merged);
                $merged['attr']['placeholder'] = $explicit;
            }

            return $merged;
        }

        // false from profile defaults, by_form or field_types clears attr.placeholder.
        if ($placeholder === false) {
            return $this->unsetAttrPlaceholder($merged);
        }

        if ($placeholder !== null && !$this->hasAttrPlaceholder($merged)) {
            $merged['attr']                = $this->attrOf($merged);
            $merged['attr']['placeholder'] = $placeholder;
        }

        return $merged;
    }

    /**
     * ChoiceType (and subtypes): the root {@code placeholder} is the empty-option label, so it stays at
     * the root (including {@code false}, which removes the empty option). Only the field-specific layers
     * (explicit options, {@code by_form.<form>.fields}, {@code field_types}) set it; generic profile /
     * by_form {@code defaults.placeholder} targets HTML placeholders and is ignored for choices.
     *
     * @param array<string, mixed> $merged
     * @param list<array<string, mixed>> $specificLayers Most specific first
     *
     * @return array<string, mixed>
     */
    private function normalizeChoicePlaceholder(array $merged, array $specificLayers): array
    {
        unset($merged['placeholder']);
        foreach ($specificLayers as $layer) {
            if (array_key_exists('placeholder', $layer)) {
                $merged['placeholder'] = $layer['placeholder'];

                break;
            }
        }

        return $merged;
    }

    /**
     * Classifies a field type for placeholder handling, walking the form registry parent chain when
     * available (so custom subtypes of ChoiceType / CheckboxType are recognised).
     */
    private function placeholderRole(string $type): string
    {
        $candidates = [$type];
        $fqcn       = str_contains($type, '\\') ? $type : $this->formTypeMap?->resolve($type);
        if ($fqcn !== null && $fqcn !== $type) {
            $candidates[] = $fqcn;
        }
        if ($fqcn !== null && $this->formRegistry !== null && $this->formRegistry->hasType($fqcn)) {
            for ($resolved = $this->formRegistry->getType($fqcn)->getParent(); $resolved !== null; $resolved = $resolved->getParent()) {
                $candidates[] = $resolved->getInnerType()::class;
            }
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, self::CHOICE_TYPES, true)) {
                return self::ROLE_CHOICE;
            }
            if (in_array($candidate, self::NO_PLACEHOLDER_TYPES, true)) {
                return self::ROLE_NO_PLACEHOLDER;
            }
        }

        return self::ROLE_INPUT;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function hasAttrPlaceholder(array $options): bool
    {
        return isset($options['attr']) && is_array($options['attr']) && array_key_exists('placeholder', $options['attr']);
    }

    /**
     * @param array<string, mixed> $merged
     *
     * @return array<string, mixed>
     */
    private function attrOf(array $merged): array
    {
        return isset($merged['attr']) && is_array($merged['attr']) ? $merged['attr'] : [];
    }

    /**
     * @param array<string, mixed> $merged
     *
     * @return array<string, mixed>
     */
    private function unsetAttrPlaceholder(array $merged): array
    {
        if (isset($merged['attr']) && is_array($merged['attr'])) {
            unset($merged['attr']['placeholder']);
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
