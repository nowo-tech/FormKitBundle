<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Port: creates multi-step wizard sessions.
 *
 * The default implementation is {@see MultiStepWizardSessionFactory}; alias this interface to your own service to
 * replace it.
 */
interface MultiStepWizardSessionFactoryInterface
{
    /**
     * @param array<string, array{label: string, fields: array<string, array<string, mixed>|string>}> $steps Step key => ['label' => '...', 'fields' => [...]]
     */
    public function create(array $steps, string $wizardName): MultiStepWizardSessionInterface;
}
