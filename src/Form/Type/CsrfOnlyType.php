<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Override;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reusable CSRF-only Symfony form for single-action POSTs (toggle, revoke, delete, …).
 *
 * Pass a unique {@see OptionsResolver} {@code csrf_token_id} when creating the form.
 * Empty block prefix keeps the CSRF field flat ({@code _token}) unless the form is
 * created with a name (e.g. {@code csrf_only[_token]} via {@see CsrfOnlyFormFactory::createNamed()}).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CsrfOnlyType extends FormKitAbstractType
{
    /** @param FormBuilderInterface<mixed> $builder */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // CSRF only — no application fields.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection'    => true,
            'csrf_token_id'      => 'csrf_only',
            'allow_extra_fields' => true,
        ]);
        $resolver->setAllowedTypes('csrf_token_id', 'string');
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return '';
    }
}
