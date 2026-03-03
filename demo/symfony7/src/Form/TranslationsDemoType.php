<?php

declare(strict_types=1);

namespace App\Form;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo form using A2lix TranslationsType for translatable fields.
 */
class TranslationsDemoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('translations', TranslationsType::class, [
                'locales'        => ['en', 'es', 'fr'],
                'default_locale' => 'en',
                'fields'         => [
                    'title' => [
                        'field_type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                        'label'      => 'Title',
                    ],
                    'description' => [
                        'field_type' => \Symfony\Component\Form\Extension\Core\Type\TextareaType::class,
                        'label'      => 'Description',
                    ],
                ],
                'locale_labels' => [
                    'en' => 'English',
                    'es' => 'Español',
                    'fr' => 'Français',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemoTranslatableItem::class,
        ]);
    }
}
