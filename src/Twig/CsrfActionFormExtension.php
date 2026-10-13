<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Twig;

use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig function {@code csrf_action_form()} for CSRF-only single-action POST forms (delete, toggle,
 * retry buttons, kit forks) built on {@see CsrfOnlyFormFactory}.
 *
 * Registered when Twig is available and {@code nowo_form_kit.twig.csrf_action_form} is true (default).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CsrfActionFormExtension extends AbstractExtension
{
    public const FUNCTION_NAME = 'csrf_action_form';

    public function __construct(
        private readonly CsrfOnlyFormFactory $csrfOnlyFormFactory,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(self::FUNCTION_NAME, $this->csrfActionForm(...)),
        ];
    }

    /**
     * @param string $action Form action URL
     * @param string $csrfTokenId CSRF token id checked by the controller
     * @param string $method HTTP method (normalised to upper case)
     * @param bool $named Nested {@code csrf_only[_token]} when true; flat token field when false (kits)
     * @param string $csrfFieldName {@code _token} or kit {@code _csrf_token}
     * @param array<string, scalar|null> $fields Optional typed fields (flat names; forces empty-prefix form, ignores $named)
     * @param array<string, string> $fieldTypes Field name => FormKit snake type (default hidden)
     * @param array<string, array<string, mixed>> $fieldOptions Per-field Form Type options
     */
    public function csrfActionForm(
        string $action,
        string $csrfTokenId,
        string $method = 'POST',
        bool $named = true,
        string $csrfFieldName = '_token',
        array $fields = [],
        array $fieldTypes = [],
        array $fieldOptions = [],
    ): FormView {
        if ($fields !== []) {
            return $this->csrfOnlyFormFactory
                ->createWithFields($action, $csrfTokenId, $fields, $method, $csrfFieldName, $fieldTypes, $fieldOptions)
                ->createView();
        }

        if ($named) {
            return $this->csrfOnlyFormFactory
                ->createNamed($action, $csrfTokenId, $method, $csrfFieldName)
                ->createView();
        }

        return $this->csrfOnlyFormFactory
            ->create($action, $csrfTokenId, $method, $csrfFieldName)
            ->createView();
    }
}
