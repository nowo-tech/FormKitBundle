<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: FormKitAbstractType + FormKitTrait (snake_case types) with withBuilder / add*Field / buildFieldsFromArray.
 *
 * @see docs/USAGE.md "FormKitTrait and FormKitAbstractType"
 */
final class SnakeCaseKitDemoType extends FormKitAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->withBuilder($builder, function () use ($rowHalf, $rowFull): void {
            $this->addTextField('full_name', $rowHalf);
            $this->addEmailField('email_address', $rowHalf);
            $this->buildFieldsFromArray([
                'topic' => [
                    'type'    => 'choice',
                    'choices' => [
                        'Support' => 'support',
                        'Sales'   => 'sales',
                        'Other'   => 'other',
                    ],
                    'row_attr' => $rowFull['row_attr'],
                ],
                'notes' => [
                    'type'     => 'textarea',
                    'required' => false,
                    'row_attr' => $rowFull['row_attr'],
                ],
            ]);
            $this->addNamedField('priority', 'integer', array_merge($rowHalf, [
                'required' => false,
            ]));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
