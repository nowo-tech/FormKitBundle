<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use A2lix\TranslationFormBundle\Form\Type\TranslationsFormsType as A2lixTranslationsFormsType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Wrapper around A2lix TranslationsFormsType for reuse with a convention-based API.
 *
 * Accepts <code>form_type</code> and <code>data_class</code> at top level; merges
 * <code>data_class</code> into <code>form_options</code> so the inner form per locale
 * receives it. Requires <code>a2lix/translation-form-bundle</code>.
 *
 * Usage:
 * <code>
 *   $builder->add('translations', TranslationsFormsType::class, [
 *       'form_type' => TranslationItemType::class,
 *       'data_class' => TranslationItem::class,
 *   ]);
 * </code>
 */
final class TranslationsFormsType extends A2lixTranslationsFormsType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('form_type');
        $resolver->setAllowedTypes('form_type', 'string');
        $resolver->setDefaults([
            'form_options' => [],
            'enabled_locales' => ['en', 'es', 'fr'],
            'required_locales' => [],
            'locale_labels' => null,
            'theming_granularity' => 'field',
        ]);
        $resolver->setAllowedTypes('form_options', 'array');
        $resolver->setAllowedTypes('enabled_locales', 'array');
        $resolver->setAllowedTypes('required_locales', 'array');
        $resolver->setAllowedTypes('locale_labels', ['array', 'null']);
        $resolver->setAllowedTypes('theming_granularity', 'string');

        parent::configureOptions($resolver);

        $resolver->setDefined('data_class');
        $resolver->setNormalizer('form_options', function (Options $options, $value): array {
            $dataClass = $options['data_class'] ?? null;
            $base = $dataClass !== null ? ['data_class' => $dataClass] : [];

            return array_merge($base, $value ?? []);
        });
    }

    public function getBlockPrefix(): string
    {
        return 'nowo_translations_forms';
    }
}
