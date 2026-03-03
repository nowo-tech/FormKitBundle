<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function count;
use function is_array;

/**
 * Holds multi-step wizard state in session: current step index and collected data per step.
 *
 * Steps definition: [stepKey => ['label' => '...', 'fields' => [...]], ...]
 * Order of steps is the order of array keys. Session key: "nowo_form_kit_wizard_{wizardName}".
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MultiStepWizardSession
{
    /** @var array<string, array{label: string, fields: array}> */
    private array $steps;

    private string $wizardName;

    private SessionInterface $session;

    private string $sessionKey;

    /**
     * @param array<string, array{label: string, fields: array}> $steps Step key => [label, fields]
     */
    public function __construct(
        array $steps,
        string $wizardName,
        RequestStack $requestStack
    ) {
        $this->steps      = $steps;
        $this->wizardName = $wizardName;
        $this->session    = $requestStack->getSession();
        $this->sessionKey = 'nowo_form_kit_wizard_' . $wizardName;
    }

    /** @return list<string> Step keys in order */
    public function getStepKeys(): array
    {
        return array_keys($this->steps);
    }

    public function getCurrentStepKey(): string
    {
        $keys = $this->getStepKeys();
        $idx  = $this->getCurrentIndex();
        if ($idx >= count($keys)) {
            return (string) end($keys);
        }

        return $keys[$idx];
    }

    public function getCurrentIndex(): int
    {
        $bag = $this->session->get($this->sessionKey, ['index' => 0, 'data' => []]);

        return (int) ($bag['index'] ?? 0);
    }

    /** @return array<string, mixed> All collected data keyed by step */
    public function getCollectedData(): array
    {
        $bag = $this->session->get($this->sessionKey, ['index' => 0, 'data' => []]);

        return is_array($bag['data'] ?? []) ? $bag['data'] : [];
    }

    /** @return array<string, mixed> Flat map of all field names to values (for summary) */
    public function getCollectedDataFlat(): array
    {
        $flat = [];
        foreach ($this->getCollectedData() as $stepData) {
            if (is_array($stepData)) {
                $flat = array_merge($flat, $stepData);
            }
        }

        return $flat;
    }

    /** @param array<string, mixed> $data This step's form data */
    public function setStepData(string $stepKey, array $data): void
    {
        $bag               = $this->session->get($this->sessionKey, ['index' => 0, 'data' => []]);
        $dataBag           = is_array($bag['data'] ?? []) ? $bag['data'] : [];
        $dataBag[$stepKey] = $data;
        $bag['data']       = $dataBag;
        $this->session->set($this->sessionKey, $bag);
    }

    public function advance(): void
    {
        $bag          = $this->session->get($this->sessionKey, ['index' => 0, 'data' => []]);
        $idx          = (int) ($bag['index'] ?? 0);
        $bag['index'] = min($idx + 1, count($this->getStepKeys()));
        $this->session->set($this->sessionKey, $bag);
    }

    public function reset(): void
    {
        $this->session->set($this->sessionKey, ['index' => 0, 'data' => []]);
    }

    public function isComplete(): bool
    {
        return $this->getCurrentIndex() >= count($this->getStepKeys());
    }

    public function getStepLabel(string $stepKey): string
    {
        return $this->steps[$stepKey]['label'] ?? $stepKey;
    }

    /** @return array<string, mixed> Field definition for this step */
    public function getStepFields(string $stepKey): array
    {
        return $this->steps[$stepKey]['fields'] ?? [];
    }

    /** @return array<string, array{label: string, fields: array}> */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
