<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Locale tabs demo (FormKit 2.13): the three shapes in one form.
 *  (a) `translations`: a list of translation entities (CollectionType + locale_tabs, mode "entries");
 *  (b) `name_<locale>` / `description_<locale>`: flat unmapped fields grouped by the _locale_tabs include (mode "fields");
 *  (c) `labels`: a JSON {locale: text} map (TranslatableMapType, required locale "en");
 *  plus `slugs` (children keyed by locale) on the neutral renderer (`ui_kit: false`).
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class LocaleTabsDemoType extends AbstractType
{
    public const LOCALES = ['en', 'es', 'fr', 'de'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('translations', CollectionType::class, [
            'label'              => 'Article (list of translation entities)',
            'translation_domain' => false,
            'entry_type'         => LocaleTabsEntryType::class,
            'locale_tabs'        => ['default_locale' => 'en', 'required_locales' => ['en']],
        ]);

        foreach (self::LOCALES as $locale) {
            $builder
                ->add('name_' . $locale, TextType::class, ['label' => 'Name', 'translation_domain' => false, 'mapped' => false, 'required' => $locale === 'en', 'constraints' => $locale === 'en' ? [new NotBlank()] : []])
                ->add('description_' . $locale, TextType::class, ['label' => 'Description', 'translation_domain' => false, 'mapped' => false, 'required' => false]);
        }

        $builder->add('labels', TranslatableMapType::class, [
            'label'              => 'Menu label (JSON map)',
            'translation_domain' => false,
            'locales'            => self::LOCALES,
            'default_locale'     => 'en',
            'required_locales'   => ['en'],
        ]);

        $builder->add('slugs', FormType::class, [
            'label'              => 'Slug (neutral FormKit markup, ui_kit: false)',
            'translation_domain' => false,
            'locale_tabs'        => ['ui_kit' => false, 'default_locale' => 'en', 'label' => 'Slug (%locale%)'],
        ]);
        foreach (self::LOCALES as $locale) {
            $builder->get('slugs')->add($locale, TextType::class, ['required' => false]);
        }
    }
}
