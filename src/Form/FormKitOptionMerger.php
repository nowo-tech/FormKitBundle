<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

final class FormKitOptionMerger
{
    public function __construct(
        private readonly ?string $translationDomain,
        private readonly bool $autoLabel,
        private readonly bool $autoPlaceholder,
        private readonly bool $autoHelp,
        private readonly array $optionsConfig,
        private readonly array $attrConfig,
        private readonly array $rowAttrConfig,
    ) {
    }

    public function merge(string $formNameSnake, string $fieldName, string $fieldTypeSnake, array $explicitOptions = []): array
    {
        $opts = $this->mergeCascade($formNameSnake, $fieldName, $fieldTypeSnake, $explicitOptions);
        $this->applyAutoTranslationKeys($formNameSnake, $fieldName, $opts);
        $this->applyAttrAndRowAttr($fieldTypeSnake, $opts);
        return $opts;
    }

    private function mergeCascade(string $formNameSnake, string $fieldName, string $fieldTypeSnake, array $explicitOptions): array
    {
        $minimum = $this->optionsConfig['minimum'] ?? [];
        $byType = $this->optionsConfig['by_field_type'][$fieldTypeSnake] ?? [];
        $byForm = $this->optionsConfig['by_form'][$formNameSnake] ?? [];
        $byField = $this->optionsConfig['by_field'][$formNameSnake][$fieldName] ?? [];
        return array_merge(
            is_array($minimum) ? $minimum : [],
            is_array($byType) ? $byType : [],
            is_array($byForm) ? $byForm : [],
            is_array($byField) ? $byField : [],
            $explicitOptions,
        );
    }

    private function applyAutoTranslationKeys(string $formNameSnake, string $fieldName, array &$opts): void
    {
        $prefix = $formNameSnake . '.' . $fieldName . '.';
        if ($this->autoLabel && !array_key_exists('label', $opts)) {
            $opts['label'] = $prefix . 'label';
        }
        if ($this->autoPlaceholder) {
            $opts['attr'] = $opts['attr'] ?? [];
            if (!array_key_exists('placeholder', $opts['attr'])) {
                $opts['attr']['placeholder'] = $prefix . 'placeholder';
            }
        }
        if ($this->autoHelp && !array_key_exists('help', $opts)) {
            $opts['help'] = $prefix . 'help';
        }
        if ($this->translationDomain !== null && !array_key_exists('translation_domain', $opts)) {
            $opts['translation_domain'] = $this->translationDomain;
        }
    }

    private function applyAttrAndRowAttr(string $fieldTypeSnake, array &$opts): void
    {
        $attrDefault = $this->attrConfig['default'] ?? [];
        $attrByType = $this->attrConfig['by_type'][$fieldTypeSnake] ?? [];
        $attrClasses = array_merge(is_array($attrDefault) ? $attrDefault : [], is_array($attrByType) ? $attrByType : []);
        if ($attrClasses !== []) {
            $opts['attr'] = $opts['attr'] ?? [];
            $existing = isset($opts['attr']['class']) ? $opts['attr']['class'] . ' ' : '';
            $opts['attr']['class'] = trim($existing . implode(' ', $attrClasses));
        }
        $rowDefault = $this->rowAttrConfig['default'] ?? [];
        $rowByType = $this->rowAttrConfig['by_type'][$fieldTypeSnake] ?? [];
        $rowClasses = array_merge(is_array($rowDefault) ? $rowDefault : [], is_array($rowByType) ? $rowByType : []);
        if ($rowClasses !== []) {
            $opts['row_attr'] = $opts['row_attr'] ?? [];
            $existing = isset($opts['row_attr']['class']) ? $opts['row_attr']['class'] . ' ' : '';
            $opts['row_attr']['class'] = trim($existing . implode(' ', $rowClasses));
        }
    }
}
