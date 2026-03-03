<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

use function is_string;
use function sprintf;

/**
 * Trait for form types that use FormOptionsMerger for convention-based options.
 *
 * Inject FormOptionsMerger into your form type (e.g. via service definition). Use either
 * addWithDefaults($builder, $name, TextType::class, []) or the Phase 2 helpers
 * addText(), addEmail(), addTextarea(), etc. (field name + options only, no type class).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait FormOptionsTrait
{
    private FormOptionsMerger $formOptionsMerger;

    /** Config name (key in nowo_form_kit.configs) to use; null = default_config */
    private ?string $formKitConfigName = null;

    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        $this->formOptionsMerger = $formOptionsMerger;
    }

    /** Set which config to use (key in configs); null uses default_config. */
    public function setFormKitConfigName(?string $configName): void
    {
        $this->formKitConfigName = $configName;
    }

    /**
     * Adds a child to the builder with options merged by convention and config.
     *
     * Form name is taken from getBlockPrefix(); label, placeholder and help default to
     * translation keys "form_snake.field_snake.label", ".placeholder", ".help" unless
     * you pass false for any of them in $options. Uses the config set via setFormKitConfigName() or default_config.
     *
     * @param array<string, mixed> $options Field-specific options (override convention; use false to disable label/placeholder/help)
     */
    protected function addWithDefaults(
        FormBuilderInterface $builder,
        string $name,
        string $type,
        array $options = []
    ): void {
        $formName = $this->getBlockPrefix();
        $merged   = $this->formOptionsMerger->resolve($formName, $name, $type, $options, $this->formKitConfigName);
        $builder->add($name, $type, $merged);
    }

    /**
     * Build form from an array of field definitions.
     *
     * Each key is the field name. Value can be:
     * - A string: the form type FQCN (e.g. TextType::class).
     * - An array with required key "type" (FQCN) and any other options for that field.
     *
     * @param array<string, array{type: string, ...}|string> $fields e.g. ['full_name' => TextType::class, 'topic' => ['type' => ChoiceType::class, 'choices' => [...]]]
     */
    protected function buildFormFromArray(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $name => $definition) {
            if (is_string($definition)) {
                $this->addWithDefaults($builder, $name, $definition, []);
            } else {
                $type = $definition['type'] ?? null;
                if ($type === null || $type === '') {
                    throw new InvalidArgumentException(sprintf('Field "%s" must have a non-empty "type" key.', $name));
                }
                $options = $definition;
                unset($options['type']);
                $this->addWithDefaults($builder, $name, $type, $options);
            }
        }
    }

    // --- Phase 2: add-by-type helpers (field name + options only, no type class) ---

    /** @param array<string, mixed> $options */
    protected function addText(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, TextType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addEmail(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, EmailType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTextarea(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, TextareaType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPassword(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, PasswordType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addUrl(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, UrlType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addInteger(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, IntegerType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addNumber(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, NumberType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCheckbox(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, CheckboxType::class, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addChoice(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }
}
