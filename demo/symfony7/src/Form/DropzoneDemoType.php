<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Type\DropzoneFieldType;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dropzone demo using the bundle: FormOptionsTrait + custom type layer (DropzoneFieldType).
 * Label, placeholder and help come from convention (dropzone_demo.document.*).
 */
class DropzoneDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'document', DropzoneFieldType::class, []);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
