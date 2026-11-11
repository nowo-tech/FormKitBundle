<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\ChoiceFieldsDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Showcases addSelectField, addMultiSelectField, addChoiceRadiosField, addChoiceCheckboxesField,
 * addCheckboxField, addMultiSelectSelectAllField (when nowo-tech/select-all-choice-bundle is installed),
 * and documents addAutocompleteField for UX Autocomplete FQCNs; plus the 2.22 field widgets (choice_cards, switch,
 * char_counter) on unmapped fields.
 */
final class ChoiceFieldsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->withBuilder($builder, function () use ($rowHalf, $rowFull): void {
            $this->addSelectField('country', array_merge($rowHalf, [
                'choices' => [
                    'Spain'   => 'es',
                    'France'  => 'fr',
                    'Germany' => 'de',
                ],
                'required' => false,
            ]));

            $this->addMultiSelectField('hobbies', array_merge($rowFull, [
                'choices' => [
                    'PHP'     => 'php',
                    'Symfony' => 'symfony',
                    'Twig'    => 'twig',
                ],
                'required'   => false,
                'select_all' => true,
            ]));

            $this->addChoiceRadiosField('priority', array_merge($rowHalf, [
                'choices' => [
                    'Low'    => 'low',
                    'Normal' => 'normal',
                    'High'   => 'high',
                ],
            ]));

            $this->addChoiceCheckboxesField('tags', array_merge($rowFull, [
                'choices' => [
                    'Documentation' => 'docs',
                    'API'           => 'api',
                    'UI'            => 'ui',
                ],
                'required'   => false,
                'select_all' => true,
            ]));

            $this->addCheckboxField('agree', array_merge($rowFull, [
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
                $this->addMultiSelectSelectAllField('permissions', $permissionsOptions);
            } else {
                $this->addMultiSelectField('permissions', $permissionsOptions);
            }
        });

        // 2.22 field widgets (unmapped: shown and submitted, not stored on the model).
        $builder->add('plan', ChoiceType::class, $rowFull + [
            'mapped'       => false,
            'expanded'     => true,
            'data'         => 'pro',
            'label'        => 'choice_fields_demo.plan.label',
            'help'         => 'choice_fields_demo.plan.help',
            'help_html'    => true,
            'choices'      => ['Free' => 'free', 'Pro' => 'pro', 'Team' => 'team'],
            'choice_cards' => [
                'columns'     => 3,
                'description' => [
                    'free' => 'choice_fields_demo.plan.free',
                    'pro'  => 'choice_fields_demo.plan.pro',
                    'team' => 'choice_fields_demo.plan.team',
                ],
            ],
        ]);
        $builder->add('notifications', CheckboxType::class, $rowFull + [
            'mapped'    => false,
            'required'  => false,
            'label'     => 'choice_fields_demo.notifications.label',
            'help'      => 'choice_fields_demo.notifications.help',
            'help_html' => true,
            'switch'    => ['icons' => true],
        ]);
        $builder->add('bio', TextareaType::class, $rowFull + [
            'mapped'       => false,
            'required'     => false,
            'label'        => 'choice_fields_demo.bio.label',
            'help'         => 'choice_fields_demo.bio.help',
            'help_html'    => true,
            'constraints'  => [new Length(max: 160)],
            'char_counter' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChoiceFieldsDemoData::class,
        ]);
    }
}
