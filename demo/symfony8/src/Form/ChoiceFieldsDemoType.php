<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\ChoiceFieldsDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Showcases addSelect, addMultiSelect, addChoiceRadios, addChoiceCheckboxes, addCheckbox,
 * addMultiSelectSelectAll (when nowo-tech/select-all-choice-bundle is installed), and documents addAutocompleteField for UX Autocomplete FQCNs.
 */
final class ChoiceFieldsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->addSelect($builder, 'country', array_merge($rowHalf, [
            'choices' => [
                'Spain'   => 'es',
                'France'  => 'fr',
                'Germany' => 'de',
            ],
            'required' => false,
        ]));

        $this->addMultiSelect($builder, 'hobbies', array_merge($rowFull, [
            'choices' => [
                'PHP'     => 'php',
                'Symfony' => 'symfony',
                'Twig'    => 'twig',
            ],
            'required'   => false,
            'select_all' => true,
        ]));

        $this->addChoiceRadios($builder, 'priority', array_merge($rowHalf, [
            'choices' => [
                'Low'    => 'low',
                'Normal' => 'normal',
                'High'   => 'high',
            ],
        ]));

        $this->addChoiceCheckboxes($builder, 'tags', array_merge($rowFull, [
            'choices' => [
                'Documentation' => 'docs',
                'API'           => 'api',
                'UI'            => 'ui',
            ],
            'required'   => false,
            'select_all' => true,
        ]));

        $this->addCheckbox($builder, 'agree', array_merge($rowFull, [
            'required' => false,
        ]));

        $permissionsOptions = array_merge($rowFull, [
            'choices' => [
                'Read'   => 'read',
                'Write'  => 'write',
                'Delete' => 'delete',
            ],
            'required' => false,
        ]);

        if (class_exists('Nowo\\SelectAllChoiceBundle\\NowoSelectAllChoiceBundle')) {
            $this->addMultiSelectSelectAll($builder, 'permissions', $permissionsOptions);
        } else {
            $this->addMultiSelect($builder, 'permissions', $permissionsOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChoiceFieldsDemoData::class,
        ]);
    }
}
