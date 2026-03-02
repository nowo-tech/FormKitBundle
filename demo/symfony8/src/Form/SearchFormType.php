<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: search form (query + optional category). Convention: search_form.*
 * Use with inline/horizontal layout in template.
 */
class SearchFormType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFromArray($builder, [
            'q' => TextType::class,
            'category' => [
                'type' => ChoiceType::class,
                'choices' => [
                    'All' => '',
                    'Products' => 'products',
                    'Articles' => 'articles',
                    'Support' => 'support',
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
        ]);
    }
}
