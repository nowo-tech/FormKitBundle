<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds input_group_prefix and input_group_suffix options to form fields.
 * When set, the form theme can wrap the widget in Bootstrap's input-group
 * and show the prefix/suffix (e.g. @ for email, lock icon for password).
 */
final class InputGroupExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'input_group_prefix' => null,
            'input_group_suffix' => null,
        ]);
        $resolver->setAllowedTypes('input_group_prefix', ['string', 'null']);
        $resolver->setAllowedTypes('input_group_suffix', ['string', 'null']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['input_group_prefix'] = $options['input_group_prefix'];
        $view->vars['input_group_suffix'] = $options['input_group_suffix'];
    }
}
