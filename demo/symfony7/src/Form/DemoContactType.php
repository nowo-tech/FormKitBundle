<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\FormKitBundle\Form\Type\StaticAlertType;
use Nowo\FormKitBundle\Form\Type\StaticSeparatorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo form type: all Form Kit Phase 2 field types, defined via array.
 * Convention: demo_contact.field_name.label, .placeholder, .help.
 */
class DemoContactType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowThird = ['row_attr' => ['class' => 'col-12 col-md-4 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->buildFormFromArray($builder, [
            'full_name' => ['type' => TextType::class, ...$rowHalf],
            'email_address' => [
                'type' => EmailType::class,
                'input_group_prefix' => '@',
                ...$rowHalf,
            ],
            'message' => ['type' => TextareaType::class, ...$rowFull],
            '_notice_contact' => [
                'type' => StaticAlertType::class,
                'message' => 'demo_contact.notice_contact',
                'label' => false,
                ...$rowFull,
            ],
            '_sep_details' => ['type' => StaticSeparatorType::class, 'label' => false, ...$rowFull],
            'password' => [
                'type' => PasswordType::class,
                'input_group_prefix' => '🔒',
                ...$rowHalf,
            ],
            'website' => [
                'type' => UrlType::class,
                'input_group_suffix' => '🔗',
                ...$rowHalf,
            ],
            'age' => ['type' => IntegerType::class, ...$rowThird],
            'score' => ['type' => NumberType::class, ...$rowThird],
            'topic' => [
                'type' => ChoiceType::class,
                'choices' => [
                    'Support' => 'support',
                    'Sales' => 'sales',
                    'Other' => 'other',
                ],
                ...$rowThird,
            ],
            '_sep_terms' => ['type' => StaticSeparatorType::class, 'label' => false, ...$rowFull],
            '_terms_notice' => [
                'type' => StaticAlertType::class,
                'message' => 'demo_contact.terms_notice',
                'label' => false,
                ...$rowFull,
            ],
            'accept_terms' => ['type' => CheckboxType::class, ...$rowFull],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
