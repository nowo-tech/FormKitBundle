<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: example form (name, email, subject, message). Convention: example_form.*
 * Use with card/stacked layout in template.
 */
class ExampleFormType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'nowo-ui-form-row']];
        $rowFull = ['row_attr' => ['class' => 'nowo-ui-form-row']];

        $this->buildFormFromArray($builder, [
            'name'    => ['type' => TextType::class, ...$rowHalf],
            'email'   => ['type' => EmailType::class, ...$rowHalf],
            'subject' => ['type' => TextType::class, ...$rowFull],
            'message' => ['type' => TextareaType::class, ...$rowFull],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
