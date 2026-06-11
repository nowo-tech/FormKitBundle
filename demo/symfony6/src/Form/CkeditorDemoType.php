<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\CkeditorDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Showcases addCKEditorField() with two FOS configs (standard vs basic toolbar).
 */
final class CkeditorDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->addCKEditorField($builder, 'body', array_merge($rowFull, [
            'required' => false,
        ]));

        $this->addCKEditorField($builder, 'excerpt', array_merge($rowFull, [
            'required'    => false,
            'config_name' => 'minimal',
        ]));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CkeditorDemoData::class,
        ]);
    }
}
