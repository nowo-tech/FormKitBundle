<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\Form\FormBuilderInterface;

use function is_string;
use function sprintf;

/**
 * Trait for form types that want cascading option merge and add-by-type helpers.
 *
 * Requires the form to have FormOptionsMerger and FormTypeMap set (e.g. via FormKitAbstractType).
 * Prefer withBuilder() + addTextField() when adding many fields without repeating $builder.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait FormKitTrait
{
    protected FormOptionsMerger $formOptionsMerger;
    protected FormTypeMap $formTypeMap;

    /** Builder bound by {@see withBuilder()}; used by add*Field() helpers. */
    private ?FormBuilderInterface $formKitBoundBuilder = null;

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
     * Bind $builder for the duration of $callback so add*Field() helpers can omit it.
     *
     * Nested calls restore the previous builder (or null) when the inner callback returns.
     *
     * @param callable(): void $callback
     */
    protected function withBuilder(FormBuilderInterface $builder, callable $callback): void
    {
        $previous                   = $this->formKitBoundBuilder;
        $this->formKitBoundBuilder = $builder;

        try {
            $callback();
        } finally {
            $this->formKitBoundBuilder = $previous;
        }
    }

    /**
     * Builder currently bound by {@see withBuilder()}.
     *
     * @throws LogicException when called outside withBuilder()
     */
    protected function boundBuilder(): FormBuilderInterface
    {
        if ($this->formKitBoundBuilder === null) {
            throw new LogicException('No form builder is bound. Call add*Field() (or boundBuilder()) inside withBuilder($builder, …).');
        }

        return $this->formKitBoundBuilder;
    }

    /**
     * Merge options in cascade and apply auto label/placeholder/help and attr/row_attr.
     *
     * @param array<string, mixed> $options Field-specific options
     *
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
     *
     * @throws InvalidArgumentException When type is not in the map
     */
    protected function addField(FormBuilderInterface $builder, string $name, string $typeSnakeCase, array $options = []): void
    {
        $fqcn = $this->formTypeMap->resolve($typeSnakeCase);
        if ($fqcn === null) {
            throw new InvalidArgumentException(sprintf('Unknown form type snake_case name "%s". Register it in nowo_form_kit.type_map or use a built-in type.', $typeSnakeCase));
        }
        $builder->add($name, $fqcn, $this->mergeFieldOptions($name, $typeSnakeCase, $options));
    }

    /**
     * Like {@see addField()} using the builder from {@see withBuilder()}.
     *
     * @param array<string, mixed> $options
     */
    protected function addNamedField(string $name, string $typeSnakeCase, array $options = []): void
    {
        $this->addField($this->boundBuilder(), $name, $typeSnakeCase, $options);
    }

    /**
     * Build form from an array of field definitions.
     *
     * Each key is the field name. Value can be:
     * - A string: the snake_case type (e.g. 'text', 'email').
     * - An array with required key "type" (snake_case) and any other options for that field.
     *
     * @param array<string, array{type: string, ...}|string> $fields e.g. ['full_name' => 'text', 'topic' => ['type' => 'choice', 'choices' => [...]]]
     */
    protected function buildFormFromArray(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $name => $definition) {
            if (is_string($definition)) {
                $this->addField($builder, $name, $definition, []);
            } else {
                $type = $definition['type'] ?? null;
                if ($type === null || $type === '') {
                    throw new InvalidArgumentException(sprintf('Field "%s" must have a non-empty "type" key.', $name));
                }
                $options = $definition;
                unset($options['type']);
                $this->addField($builder, $name, $type, $options);
            }
        }
    }

    /**
     * Like {@see buildFormFromArray()} using the builder from {@see withBuilder()}.
     *
     * @param array<string, array{type: string, ...}|string> $fields
     */
    protected function buildFieldsFromArray(array $fields): void
    {
        $this->buildFormFromArray($this->boundBuilder(), $fields);
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

    // --- Bound-builder helpers (use inside withBuilder(); no $builder argument) ---

    /** @param array<string, mixed> $options */
    protected function addTextField(string $name, array $options = []): void
    {
        $this->addText($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addEmailField(string $name, array $options = []): void
    {
        $this->addEmail($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTextareaField(string $name, array $options = []): void
    {
        $this->addTextarea($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPasswordField(string $name, array $options = []): void
    {
        $this->addPassword($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addUrlField(string $name, array $options = []): void
    {
        $this->addUrl($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addIntegerField(string $name, array $options = []): void
    {
        $this->addInteger($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addNumberField(string $name, array $options = []): void
    {
        $this->addNumber($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCheckboxField(string $name, array $options = []): void
    {
        $this->addCheckbox($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addChoiceField(string $name, array $options = []): void
    {
        $this->addChoice($this->boundBuilder(), $name, $options);
    }
}
