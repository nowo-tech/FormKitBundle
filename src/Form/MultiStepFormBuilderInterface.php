<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormInterface as SymfonyFormInterface;

/**
 * Port: builds the form of one step of a multi-step wizard.
 *
 * The default implementation is {@see MultiStepFormBuilder}; alias this interface to your own service to replace it.
 */
interface MultiStepFormBuilderInterface
{
    /**
     * Creates a form containing only the fields for the given step.
     *
     * @param array<string, array{type?: string, ...}|string> $fieldsDefinition name => FQCN or name => ['type' => ..., ...]
     * @param array<string, mixed> $data Initial data for this step's fields
     *
     * @return SymfonyFormInterface<mixed> Form with only this step's fields, ready for handleRequest
     */
    public function createStepForm(
        string $wizardName,
        string $stepKey,
        array $fieldsDefinition,
        array $data = [],
        ?string $configName = null,
    ): SymfonyFormInterface;
}
