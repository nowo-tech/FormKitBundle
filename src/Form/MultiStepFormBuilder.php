<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface as SymfonyFormInterface;
use Symfony\Component\Form\FormTypeInterface;

use function is_string;
use function sprintf;

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
final readonly class MultiStepFormBuilder
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private FormOptionsMerger $formOptionsMerger
    ) {
    }

    /**
     * Creates a form containing only the fields for the given step.
     *
     * @param array<string, array{type?: string, ...}|string> $fieldsDefinition Same as buildFormFromArray (name => FQCN or name => ['type' => ..., ...])
     * @param array<string, mixed> $data Initial data for this step's fields
     *
     * @return SymfonyFormInterface<mixed> Form with only this step's fields, ready for handleRequest
     */
    public function createStepForm(
        string $wizardName,
        string $stepKey,
        array $fieldsDefinition,
        array $data = [],
        ?string $configName = null
    ): SymfonyFormInterface {
        $formName = $wizardName . '_' . $stepKey;
        $builder  = $this->formFactory->createBuilder(FormType::class, $data, []);

        foreach ($fieldsDefinition as $name => $definition) {
            if (is_string($definition)) {
                /** @var class-string<FormTypeInterface<mixed>> $type */
                $type    = $definition;
                $options = $this->formOptionsMerger->resolve($formName, $name, $type, [], $configName);
                $builder->add($name, $type, $options);
            } else {
                $type = $definition['type'] ?? null;
                if (!is_string($type) || $type === '') {
                    throw new InvalidArgumentException(sprintf('Multi-step field "%s" must have a non-empty "type" key.', $name));
                }
                $fieldOptions = $definition;
                unset($fieldOptions['type']);
                /** @var class-string<FormTypeInterface<mixed>> $type */
                $options = $this->formOptionsMerger->resolve($formName, $name, $type, $fieldOptions, $configName);
                $builder->add($name, $type, $options);
            }
        }

        return $builder->getForm();
    }
}
