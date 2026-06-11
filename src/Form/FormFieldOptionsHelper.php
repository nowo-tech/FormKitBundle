<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Small helpers for field option arrays (merge defaults, strip keys after resolve).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormFieldOptionsHelper
{
    /**
     * Defaults for an embedded subform / collection child row (Bootstrap grid full width).
     *
     * @param array<string, mixed> $fieldConfiguration
     *
     * @return array<string, mixed>
     */
    public static function mergeSubFormDefaults(array $fieldConfiguration = []): array
    {
        return array_merge([
            'row_attr' => ['class' => 'col-12 col-md-12 col-xl-12'],
            'attr'     => ['class' => 'row'],
            'label'    => false,
        ], $fieldConfiguration);
    }

    /**
     * @param array<string, mixed> $fieldConfiguration
     * @param list<string>         $keys
     *
     * @return array<string, mixed>
     */
    public static function removeKeys(array $fieldConfiguration, array $keys): array
    {
        foreach ($keys as $key) {
            unset($fieldConfiguration[$key]);
        }

        return $fieldConfiguration;
    }

    /**
     * Removes root `placeholder` and `attr.placeholder` (FormOptionsMerger stores the latter).
     *
     * @param array<string, mixed> $merged
     *
     * @return array<string, mixed>
     */
    public static function stripPlaceholderFromMergedOptions(array $merged): array
    {
        $merged = self::removeKeys($merged, ['placeholder']);
        if (isset($merged['attr']) && is_array($merged['attr'])) {
            unset($merged['attr']['placeholder']);
        }

        return $merged;
    }
}
