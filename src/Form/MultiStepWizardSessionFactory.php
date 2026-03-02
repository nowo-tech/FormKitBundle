<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates MultiStepWizardSession instances with the given steps definition and wizard name.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MultiStepWizardSessionFactory
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * @param array<string, array{label: string, fields: array}> $steps Step key => ['label' => '...', 'fields' => [...]]
     */
    public function create(array $steps, string $wizardName): MultiStepWizardSession
    {
        return new MultiStepWizardSession($steps, $wizardName, $this->requestStack);
    }
}
