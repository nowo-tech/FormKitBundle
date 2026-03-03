<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders a horizontal rule (hr) in the form flow. Use with form_renderer loop.
 */
final class StaticSeparatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'   => false,
            'label'    => false,
            'required' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'static_separator';
    }
}
