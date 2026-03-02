<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Builds a form for a single step of a multi-step wizard from an array of field definitions.
 *
 * Uses FormOptionsMerger so each field gets convention-based label, placeholder and help
 * (form name used for convention: "{wizardName}_{stepKey}").
 *
 * Step definition: ['label' => '...', 'fields' => [fieldName => Type::class|array]]
 * Fields array: same as buildFormFromArray (name => FQCN or name => ['type' => FQCN, ...options]).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class MultiStepFormBuilder
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly FormOptionsMerger $formOptionsMerger
    ) {
    }

    /**
     * Creates a form containing only the fields for the given step.
     *
     * @param array<string, string|array{type: string, ...}> $fieldsDefinition Same as buildFormFromArray (name => FQCN or name => ['type' => ..., ...])
     * @param array<string, mixed>                          $data            Initial data for this step's fields
     *
     * @return FormInterface Form with only this step's fields, ready for handleRequest
     */
    public function createStepForm(
        string $wizardName,
        string $stepKey,
        array $fieldsDefinition,
        array $data = [],
        ?string $configName = null
    ): FormInterface {
        $formName = $wizardName . '_' . $stepKey;
        $builder = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $data, []);

        foreach ($fieldsDefinition as $name => $definition) {
            if (\is_string($definition)) {
                $type = $definition;
                $options = $this->formOptionsMerger->resolve($formName, $name, $type, [], $configName);
                $builder->add($name, $type, $options);
            } else {
                $type = $definition['type'] ?? null;
                if ($type === null || $type === '') {
                    throw new \InvalidArgumentException(sprintf('Multi-step field "%s" must have a non-empty "type" key.', $name));
                }
                $fieldOptions = $definition;
                unset($fieldOptions['type']);
                $options = $this->formOptionsMerger->resolve($formName, $name, $type, $fieldOptions, $configName);
                $builder->add($name, $type, $options);
            }
        }

        return $builder->getForm();
    }
}
