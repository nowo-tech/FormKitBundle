<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Trait for form types that want cascading option merge and add-by-type helpers.
 *
 * Requires the form to have FormOptionsMerger and FormTypeMap set (e.g. via FormKitAbstractType).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait FormKitTrait
{
    protected FormOptionsMerger $formOptionsMerger;
    protected FormTypeMap $formTypeMap;

    /** Config name (key in nowo_form_kit.configs) to use; null = default_config */
    private ?string $formKitConfigName = null;

    public function setFormOptionsMerger(FormOptionsMerger $merger): void
    {
        $this->formOptionsMerger = $merger;
    }

    public function setFormTypeMap(FormTypeMap $map): void
    {
        $this->formTypeMap = $map;
    }

    /** Set which config to use (key in configs); null uses default_config. */
    public function setFormKitConfigName(?string $configName): void
    {
        $this->formKitConfigName = $configName;
    }

    /**
     * Merge options in cascade and apply auto label/placeholder/help and attr/row_attr.
     *
     * @param array<string, mixed> $options Field-specific options
     * @return array<string, mixed> Merged options for FormBuilder::add()
     */
    protected function mergeFieldOptions(string $fieldName, string $fieldTypeSnake, array $options = []): array
    {
        return $this->formOptionsMerger->resolve(
            $this->getBlockPrefix(),
            $fieldName,
            $fieldTypeSnake,
            $options,
            $this->formKitConfigName,
        );
    }

    /**
     * Add a field by snake_case type name (must exist in type map). Options are merged in cascade.
     *
     * @param array<string, mixed> $options Field-specific options
     * @throws \InvalidArgumentException When type is not in the map
     */
    protected function addField(FormBuilderInterface $builder, string $name, string $typeSnakeCase, array $options = []): void
    {
        $fqcn = $this->formTypeMap->resolve($typeSnakeCase);
        if ($fqcn === null) {
            throw new \InvalidArgumentException(sprintf('Unknown form type snake_case name "%s". Register it in nowo_form_kit.type_map or use a built-in type.', $typeSnakeCase));
        }
        $builder->add($name, $fqcn, $this->mergeFieldOptions($name, $typeSnakeCase, $options));
    }

    /**
     * Build form from an array of field definitions.
     *
     * Each key is the field name. Value can be:
     * - A string: the snake_case type (e.g. 'text', 'email').
     * - An array with required key "type" (snake_case) and any other options for that field.
     *
     * @param FormBuilderInterface $builder
     * @param array<string, string|array{type: string, ...}> $fields e.g. ['full_name' => 'text', 'topic' => ['type' => 'choice', 'choices' => [...]]]
     */
    protected function buildFormFromArray(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $name => $definition) {
            if (\is_string($definition)) {
                $this->addField($builder, $name, $definition, []);
            } else {
                $type = $definition['type'] ?? null;
                if ($type === null || $type === '') {
                    throw new \InvalidArgumentException(sprintf('Field "%s" must have a non-empty "type" key.', $name));
                }
                $options = $definition;
                unset($options['type']);
                $this->addField($builder, $name, $type, $options);
            }
        }
    }

    // --- Phase 2: add-by-type helpers (no type class needed, only field name + options) ---

    /** @param array<string, mixed> $options */
    protected function addText(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'text', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addEmail(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'email', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTextarea(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'textarea', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPassword(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'password', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addUrl(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'url', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addInteger(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'integer', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addNumber(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'number', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCheckbox(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'checkbox', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addChoice(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'choice', $options);
    }
}
