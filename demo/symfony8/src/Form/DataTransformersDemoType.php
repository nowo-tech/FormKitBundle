<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\DataTransformersDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Showcases addMoneyType, addJsonType, addCsvType, addBoolType, addSwitchType from FormOptionsTrait.
 */
final class DataTransformersDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->addMoneyType($builder, 'priceCents', $rowHalf);
        $this->addBoolType($builder, 'published', $rowHalf);
        $this->addJsonType($builder, 'metadata', $rowFull);
        $this->addCsvType($builder, 'tags', $rowFull);
        $this->addSwitchType($builder, 'notifyOn', array_merge($rowHalf, [
            'switch_value' => 1,
        ]));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DataTransformersDemoData::class,
        ]);
    }
}
