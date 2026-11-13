<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormInterface;

/**
 * Port: creates CSRF-only forms for state-changing actions (logout, delete buttons, action links).
 *
 * The default implementation is {@see CsrfOnlyFormFactory}; alias this interface to your own service to replace it.
 */
interface CsrfOnlyFormFactoryInterface
{
    /**
     * CSRF-only form with empty name (flat `_token` / custom field name).
     *
     * @param string $csrfFieldName Symfony CSRF field name (`_token` or kit `_csrf_token`)
     *
     * @return FormInterface<mixed>
     */
    public function create(
        string $action,
        string $csrfTokenId,
        string $method = 'POST',
        string $csrfFieldName = '_token',
    ): FormInterface;

    /**
     * CSRF-only form named `csrf_only` (nested `csrf_only[_token]`).
     *
     * @return FormInterface<mixed>
     */
    public function createNamed(
        string $action,
        string $csrfTokenId,
        string $method = 'POST',
        string $csrfFieldName = '_token',
    ): FormInterface;

    /**
     * CSRF form with typed flat fields (empty block prefix).
     *
     * @param array<string, scalar|null> $fields Field name => default value
     * @param array<string, string> $fieldTypes Field name => FormKit snake type (default hidden)
     * @param array<string, array<string, mixed>> $fieldOptions Per-field Form Type options
     *
     * @return FormInterface<mixed>
     */
    public function createWithFields(
        string $action,
        string $csrfTokenId,
        array $fields,
        string $method = 'POST',
        string $csrfFieldName = '_token',
        array $fieldTypes = [],
        array $fieldOptions = [],
    ): FormInterface;
}
