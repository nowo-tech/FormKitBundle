<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that renders an alert box with a translatable message in the form flow.
 * Use it inside forms rendered by the form_renderer loop for notices or help text.
 * Configure the appearance via the form theme block "static_alert_row".
 */
final class StaticAlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // No children: this type only renders static HTML via the row block.
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['message'] = $options['message'];
        $view->vars['alert_type'] = $options['alert_type'];
        $view->vars['translation_domain'] = $options['translation_domain'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
            'required' => false,
            'translation_domain' => null,
            'alert_type' => 'info',
        ]);
        $resolver->setRequired('message');
        $resolver->setAllowedValues('alert_type', ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark']);
    }

    public function getBlockPrefix(): string
    {
        return 'static_alert';
    }
}
