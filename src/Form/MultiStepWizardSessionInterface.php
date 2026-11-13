<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Port: state of one multi-step wizard (steps, current step, collected data).
 *
 * The default implementation is {@see MultiStepWizardSession} (kept in the HTTP session).
 */
interface MultiStepWizardSessionInterface
{
    /**
     * @return list<string> Step keys in order
     */
    public function getStepKeys(): array;

    public function getCurrentStepKey(): string;

    public function getCurrentIndex(): int;

    /**
     * @return array<string, mixed> All collected data keyed by step
     */
    public function getCollectedData(): array;

    /**
     * @return array<string, mixed> Flat map of all field names to values (for summary)
     */
    public function getCollectedDataFlat(): array;

    /**
     * @param array<string, mixed> $data This step's form data
     */
    public function setStepData(string $stepKey, array $data): void;

    public function advance(): void;

    public function reset(): void;

    public function isComplete(): bool;

    public function getStepLabel(string $stepKey): string;

    /**
     * @return array<string, mixed> Field definition for this step
     */
    public function getStepFields(string $stepKey): array;

    /**
     * @return array<string, array{label: string, fields: array<string, array<string, mixed>|string>}>
     */
    public function getSteps(): array;
}
