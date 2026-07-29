<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders arbitrary HTML in the form flow (e.g. a flex/grid line break). Not mapped.
 * Theme block `static_html_row` in `form/static_blocks.html.twig`.
 */
/**
 * @extends AbstractType<mixed>
 */
final class StaticHtmlType extends AbstractType
{
    /** @param FormBuilderInterface<mixed> $builder */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['html'] = $options['html'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'   => false,
            'label'    => false,
            'required' => false,
            'html'     => '<div class="w-100"></div>',
        ]);
        $resolver->setAllowedTypes('html', ['string']);
    }

    public function getBlockPrefix(): string
    {
        return 'static_html';
    }
}
