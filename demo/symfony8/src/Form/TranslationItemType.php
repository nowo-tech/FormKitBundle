<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\DemoTranslationItem;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for a single translation (title, description).
 * Used as form_type in A2lix TranslationsFormsType; uses buildFormFromArray and data_class.
 * Normalizes array (e.g. from request) to DemoTranslationItem so data_class is always satisfied.
 */
class TranslationItemType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            if (\is_array($data)) {
                $item = new DemoTranslationItem();
                $item->setTitle($data['title'] ?? null);
                $item->setDescription($data['description'] ?? null);
                $event->setData($item);
            }
        });

        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->buildFormFromArray($builder, [
            'title' => ['type' => TextType::class, ...$rowFull],
            'description' => ['type' => TextareaType::class, ...$rowFull],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemoTranslationItem::class,
        ]);
    }
}
