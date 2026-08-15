<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Creates lightweight CSRF-only forms for single POST actions.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class CsrfOnlyFormFactory
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    /**
     * CSRF-only form with empty name (flat {@code _token} / custom field name).
     *
     * @param string $csrfFieldName Symfony CSRF field name ({@code _token} or kit {@code _csrf_token})
     *
     * @return FormInterface<mixed>
     */
    public function create(
        string $action,
        string $csrfTokenId,
        string $method = 'POST',
        string $csrfFieldName = '_token',
    ): FormInterface {
        return $this->formFactory->create(CsrfOnlyType::class, null, $this->options($action, $csrfTokenId, $method, $csrfFieldName));
    }

    /**
     * CSRF-only form named {@code csrf_only} (nested {@code csrf_only[_token]}).
     *
     * @return FormInterface<mixed>
     */
    public function createNamed(
        string $action,
        string $csrfTokenId,
        string $method = 'POST',
        string $csrfFieldName = '_token',
    ): FormInterface {
        return $this->formFactory->createNamed(
            'csrf_only',
            CsrfOnlyType::class,
            null,
            $this->options($action, $csrfTokenId, $method, $csrfFieldName),
        );
    }

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
    ): FormInterface {
        $data = [];
        foreach ($fields as $name => $value) {
            $data[(string) $name] = $value === null ? '' : (string) $value;
        }

        return $this->formFactory->create(HiddenFieldsCsrfType::class, $data, [
            'action'          => $action,
            'method'          => strtoupper(trim($method)),
            'csrf_token_id'   => $csrfTokenId,
            'csrf_field_name' => $csrfFieldName,
            'fields'          => array_keys($data),
            'field_types'     => $fieldTypes,
            'field_options'   => $fieldOptions,
        ]);
    }

    /**
     * @return array{action: string, method: string, csrf_token_id: string, csrf_field_name: string}
     */
    private function options(string $action, string $csrfTokenId, string $method, string $csrfFieldName): array
    {
        return [
            'action'          => $action,
            'method'          => strtoupper(trim($method)),
            'csrf_token_id'   => $csrfTokenId,
            'csrf_field_name' => $csrfFieldName,
        ];
    }
}
