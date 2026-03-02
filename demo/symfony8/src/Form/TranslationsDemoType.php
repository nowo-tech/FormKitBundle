<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\DemoTranslationItem;
use App\Model\DemoTranslatableItem;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo form: translatable fields per locale via the bundle's TranslationsFormsType (wraps A2lix).
 * Uses buildFormFromArray; TranslationItemType also uses buildFormFromArray for title/description.
 */
class TranslationsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFromArray($builder, [
            'translations' => [
                'type' => TranslationsFormsType::class,
                'form_type' => TranslationItemType::class,
                'data_class' => DemoTranslationItem::class,
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
