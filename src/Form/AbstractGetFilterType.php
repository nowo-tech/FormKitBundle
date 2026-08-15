<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Override;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function is_array;

/**
 * Base type for idempotent GET / list filters.
 *
 * Always uses FormKit profile {@code filter} (configure it under {@code nowo_form_kit.profiles.filter}):
 * - label: never ({@code defaults.label: false})
 * - placeholder: always ({@code auto_placeholder}; catalogue {@code {prefix}.{field}.placeholder})
 * - help: always ({@code auto_help}; catalogue {@code {prefix}.{field}.help}), unless the
 *   field passes {@code help: false} (removed by FormKit merger)
 * - required: always {@code false} ({@code defaults.required: false}), unless a field opts in
 *
 * Extend this class (do not put {@code #[FormKitConfig('filter')]} on ad-hoc types
 * that extend {@see FormKitAbstractType} alone).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[FormKitConfig('filter')]
abstract class AbstractGetFilterType extends FormKitAbstractType
{
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method'          => 'GET',
            'data_class'      => null,
        ]);
    }

    /**
     * Hidden field without FormKit auto placeholder/help (pass {@code false} in options so
     * the merger unsets them — {@code HiddenType} rejects bool {@code help}).
     *
     * @param array<string, mixed> $options
     */
    protected function addHiddenFilterField(string $name, array $options = []): void
    {
        $options['placeholder'] = false;
        $options['help']        = false;
        $this->addNamedField($name, 'hidden', $options);
    }

    /**
     * Choice fields with FormKit defaults. Empty option uses catalogue
     * {@code {block_prefix}.{field}.placeholder} by default (filter {@code auto_placeholder}).
     * Pass {@code placeholder: false} to omit the empty option, or an explicit key to override.
     *
     * @param array<string, mixed> $options
     */
    protected function addFilterSelect(string $name, array $options): void
    {
        $hasExplicit = array_key_exists('placeholder', $options);
        $emptyOption = $hasExplicit ? $options['placeholder'] : true;
        unset($options['placeholder']);
        // Prevent FormKit from putting the auto key on attr.placeholder (ChoiceType empty option is root placeholder).
        $options['placeholder'] = false;

        $merged = $this->mergeFieldOptions($name, 'choice', $options);

        if ($emptyOption === true) {
            $emptyOption = $this->getBlockPrefix() . '.' . $name . '.placeholder';
        }

        if ($emptyOption !== false) {
            $merged['placeholder'] = $emptyOption;
            if (isset($merged['attr']) && is_array($merged['attr'])) {
                unset($merged['attr']['placeholder']);
            }
        }

        $this->boundBuilder()->add($name, ChoiceType::class, $merged);
    }
}
