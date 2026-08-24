<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use InvalidArgumentException;
use LogicException;
use Nowo\Ckeditor5EditorBundle\Form\Ckeditor5EditorType;
use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use Nowo\FormKitBundle\Form\Type\StaticHtmlType;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
use Nowo\IconSelectorBundle\Form\IconSelectorType;
use Nowo\OtpInputBundle\Form\OtpType;
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordToggleBundle\Form\Type\PasswordType as PasswordToggleType;
use Nowo\PhoneInputBundle\Form\Type\PhoneType;
use Nowo\SelectAllChoiceBundle\NowoSelectAllChoiceBundle;
use Nowo\SlideToConfirmBundle\Form\Type\SlideToConfirmType;
use Nowo\TagInputBundle\Form\TagType;
use Nowo\TiptapEditorBundle\Form\TiptapEditorType;
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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

use function array_key_exists;
use function class_exists;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Trait for form types that use FormOptionsMerger for convention-based options.
 *
 * Inject FormOptionsMerger into your form type (e.g. via service definition). Use either
 * addWithDefaults($builder, $name, TextType::class, []), the Phase 2 helpers
 * addText($builder, …), or withBuilder() + addTextField() / addEmailField() (no $builder per call).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait FormOptionsTrait
{
    private FormOptionsMerger $formOptionsMerger;

    /** @var FormBuilderInterface<mixed>|null Builder bound by {@see withBuilder()}; used by add*Field() helpers. */
    private ?FormBuilderInterface $formKitBoundBuilder = null;

    /** Profile name (key in nowo_form_kit.profiles) to use; null = default_profile */
    private ?string $formKitConfigName = null;

    /** True after setFormKitConfigName() or after resolving #[FormKitConfig]. */
    private bool $formKitConfigNameResolved = false;

    /** @var array<string, mixed> Optional override for translation defaults used by addTranslations(). */
    private array $formKitTranslationsDefaults = [];

    /**
     * Optional locale resolver for addTranslations().
     *
     * Signature:
     *   fn(array $options, object $subject): array{default_locale?: string, enabled_locales?: array<int, string>}
     *
     * @var callable|null
     */
    private $formKitTranslationsLocaleResolver;

    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        $this->formOptionsMerger = $formOptionsMerger;
    }

    /** Set which profile to use (key in profiles); null uses default_profile. Overrides #[FormKitConfig]. */
    public function setFormKitConfigName(?string $configName): void
    {
        $this->formKitConfigName         = $configName;
        $this->formKitConfigNameResolved = true;
    }

    /**
     * Profile name for FormOptionsMerger: explicit setter, else #[FormKitConfig] on the form class, else null (default_profile).
     */
    protected function resolvedFormKitConfigName(): ?string
    {
        if (!$this->formKitConfigNameResolved) {
            $this->formKitConfigName         = FormKitConfig::nameFrom($this);
            $this->formKitConfigNameResolved = true;
        }

        return $this->formKitConfigName;
    }

    /** @param array<string, mixed> $defaults */
    public function setFormKitTranslationsDefaults(array $defaults): void
    {
        $this->formKitTranslationsDefaults = $defaults;
    }

    /** @param callable|null $resolver */
    public function setFormKitTranslationsLocaleResolver($resolver): void
    {
        $this->formKitTranslationsLocaleResolver = $resolver;
    }

    /**
     * Bind $builder for the duration of $callback so add*Field() helpers can omit it.
     *
     * Nested calls restore the previous builder (or null) when the inner callback returns.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param callable(): void $callback
     */
    protected function withBuilder(FormBuilderInterface $builder, callable $callback): void
    {
        $previous                  = $this->formKitBoundBuilder;
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
     * Useful for helpers that still require an explicit builder (e.g. addAutocompleteField, addCKEditorField).
     *
     * @throws LogicException when called outside withBuilder()
     *
     * @return FormBuilderInterface<mixed>
     */
    protected function boundBuilder(): FormBuilderInterface
    {
        if ($this->formKitBoundBuilder === null) {
            throw new LogicException('No form builder is bound. Call add*Field() (or boundBuilder()) inside withBuilder($builder, …).');
        }

        return $this->formKitBoundBuilder;
    }

    /**
     * Resolve merged field options (conventions + config) without adding the field.
     *
     * Useful in FormEvents listeners where you only have a FormInterface and call $form->add(...).
     *
     * @param class-string $type Form type FQCN
     * @param array<string, mixed> $options Field-specific options (override convention; use false to disable label/placeholder/help)
     *
     * @return array<string, mixed>
     */
    protected function resolveFieldOptions(string $name, string $type, array $options = []): array
    {
        return $this->formOptionsMerger->resolve(
            $this->getBlockPrefix(),
            $name,
            $type,
            $options,
            $this->resolvedFormKitConfigName(),
        );
    }

    /**
     * Adds a child to the builder with options merged by convention and config.
     *
     * Form name is taken from getBlockPrefix(); label, placeholder and help default to
     * translation keys "form_snake.field_snake.label", ".placeholder", ".help" unless
     * you pass false for any of them in $options. Uses the profile set via setFormKitConfigName() or default_profile.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $options Field-specific options (override convention; use false to disable label/placeholder/help)
     */
    protected function addWithDefaults(
        FormBuilderInterface $builder,
        string $name,
        string $type,
        array $options = []
    ): void {
        if (!array_key_exists('translation_domain', $options)) {
            $formDomain = $builder->getOption('translation_domain');
            if (is_string($formDomain) && $formDomain !== '') {
                $options['translation_domain'] = $formDomain;
            }
        }

        $builder->add($name, $type, $this->resolveFieldOptions($name, $type, $options));
    }

    /**
     * Defaults for embedded subforms / collection item rows (Bootstrap grid full width).
     * Merge the result into options for `CollectionType`, nested `FormType`, etc.
     *
     * @param array<string, mixed> $fieldConfiguration
     *
     * @return array<string, mixed>
     */
    protected function mergeSubFormFieldOptions(array $fieldConfiguration = []): array
    {
        return FormFieldOptionsHelper::mergeSubFormDefaults($fieldConfiguration);
    }

    /**
     * Removes keys from a resolved field options array (e.g. after FormOptionsMerger::resolve()).
     *
     * @param array<string, mixed> $fieldConfiguration
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    protected function removeFieldOptionKeys(array $fieldConfiguration, array $keys): array
    {
        return FormFieldOptionsHelper::removeKeys($fieldConfiguration, $keys);
    }

    /**
     * Inserts a full-width line break for flex/grid layouts (default: Bootstrap `w-100` div).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options Extra field options merged with defaults (label/placeholder/help disabled)
     */
    protected function addFieldBreak(FormBuilderInterface $builder, ?string $fieldName = null, ?string $html = null, array $options = []): void
    {
        $name = $fieldName ?? ('field_break_' . bin2hex(random_bytes(4)));
        $base = [
            'label'       => false,
            'placeholder' => false,
            'help'        => false,
            'html'        => $html ?? '<div class="w-100"></div>',
        ];
        $this->addWithDefaults($builder, $name, StaticHtmlType::class, array_merge($base, $options));
    }

    /**
     * Adds A2lix translations field with sane defaults (agnostic to project-specific Partner model).
     *
     * Required option:
     * - form_type: class-string (inner translation item form type)
     *
     * Locale context resolution order:
     * 1) callable set via setFormKitTranslationsLocaleResolver()
     * 2) protected method resolveFormKitTranslationsLocaleContext(array $options): array (if exists)
     * 3) fallback defaults: default_locale = "en", enabled_locales = ["en"]
     *
     * @param FormBuilderInterface<mixed>|FormInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws InvalidArgumentException
     *
     * @phpstan-return FormBuilderInterface<mixed>|FormInterface<mixed>
     */
    protected function addTranslations(FormBuilderInterface|FormInterface $builder, array $options): FormBuilderInterface|FormInterface
    {
        if (!array_key_exists('form_type', $options) || !is_string($options['form_type']) || $options['form_type'] === '') {
            throw new InvalidArgumentException('The "form_type" key is required in addTranslations() options.');
        }

        $localeContext   = $this->resolveTranslationsLocaleContext($options);
        $defaultLocale   = $localeContext['default_locale'] ?? 'en';
        $enabledLocales  = $localeContext['enabled_locales'] ?? [$defaultLocale];
        $requiredLocales = [$defaultLocale];

        if (array_key_exists('required_locales', $localeContext)) {
            $requiredLocales = $localeContext['required_locales'];
        }

        $defaultOptions = array_merge([
            'label'            => false,
            'enabled_locales'  => $enabledLocales,
            'default_locale'   => $defaultLocale,
            'required_locales' => $requiredLocales,
            'row_attr'         => ['class' => 'col-12 mb-1'],
            'form_options'     => [],
            // Keep null by default to avoid strict data_class issues in intermediate A2lix view data.
            'data_class' => null,
        ], $this->formKitTranslationsDefaults);

        $finalOptions = array_replace_recursive($defaultOptions, $options);

        if (!isset($finalOptions['form_options']) || !is_array($finalOptions['form_options'])) {
            $finalOptions['form_options'] = [];
        }
        if (!isset($finalOptions['form_options']['row_attr']) || !is_array($finalOptions['form_options']['row_attr'])) {
            $finalOptions['form_options']['row_attr'] = [];
        }
        if (!isset($finalOptions['form_options']['attr']) || !is_array($finalOptions['form_options']['attr'])) {
            $finalOptions['form_options']['attr'] = [];
        }

        $rowClass                                          = trim(($finalOptions['form_options']['row_attr']['class'] ?? '') . ' row');
        $attrClass                                         = trim(($finalOptions['form_options']['attr']['class'] ?? '') . ' row');
        $finalOptions['form_options']['row_attr']['class'] = $rowClass;
        $finalOptions['form_options']['attr']['class']     = $attrClass;

        return $builder->add('translations', TranslationsFormsType::class, $finalOptions);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{default_locale?: string, enabled_locales?: array<int, string>, required_locales?: array<int, string>}
     */
    private function resolveTranslationsLocaleContext(array $options): array
    {
        if ($this->formKitTranslationsLocaleResolver !== null) {
            $result = ($this->formKitTranslationsLocaleResolver)($options, $this);
            if (is_array($result)) {
                return $result;
            }
        }

        if (method_exists($this, 'resolveFormKitTranslationsLocaleContext')) {
            /** @phpstan-ignore-next-line */
            $result = $this->resolveFormKitTranslationsLocaleContext($options);
            if (is_array($result)) {
                return $result;
            }
        }

        return [
            'default_locale'  => 'en',
            'enabled_locales' => ['en'],
        ];
    }

    /**
     * Build form from an array of field definitions.
     *
     * Each key is the field name. Value can be:
     * - A string: the form type FQCN (e.g. TextType::class).
     * - An array with required key "type" (FQCN) and any other options for that field.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, array{type?: string, ...}|class-string> $fields e.g. ['full_name' => TextType::class, 'topic' => ['type' => ChoiceType::class, 'choices' => [...]]]
     */
    protected function buildFormFromArray(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $name => $definition) {
            if (is_string($definition)) {
                /** @var class-string<FormTypeInterface<mixed>> $typeFqcn */
                $typeFqcn = $definition;
                $this->addWithDefaults($builder, $name, $typeFqcn, []);
            } else {
                $type = $definition['type'] ?? null;
                if (!is_string($type) || $type === '') {
                    throw new InvalidArgumentException(sprintf('Field "%s" must have a non-empty "type" key.', $name));
                }
                $options = $definition;
                unset($options['type']);
                /** @var class-string<FormTypeInterface<mixed>> $typeFqcn */
                $typeFqcn = $type;
                $this->addWithDefaults($builder, $name, $typeFqcn, $options);
            }
        }
    }

    // --- Phase 2: add-by-type helpers (field name + options only, no type class) ---

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addText(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, TextType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addEmail(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, EmailType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addTextarea(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, TextareaType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addPassword(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, PasswordType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addUrl(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, UrlType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addInteger(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, IntegerType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addNumber(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, NumberType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addCheckbox(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, CheckboxType::class, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoice(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    // --- Bound-builder helpers (use inside withBuilder(); no $builder argument) ---

    /**
     * Like {@see addWithDefaults()} using the builder from {@see withBuilder()}.
     *
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $options
     */
    protected function addTypedField(string $name, string $type, array $options = []): void
    {
        $this->addWithDefaults($this->boundBuilder(), $name, $type, $options);
    }

    /**
     * Like {@see buildFormFromArray()} using the builder from {@see withBuilder()}.
     *
     * @param array<string, array{type?: string, ...}|class-string> $fields
     */
    protected function buildFieldsFromArray(array $fields): void
    {
        $this->buildFormFromArray($this->boundBuilder(), $fields);
    }

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

    /** @param array<string, mixed> $options */
    protected function addSelectField(string $name, array $options = []): void
    {
        $this->addSelect($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addMultiSelectField(string $name, array $options = []): void
    {
        $this->addMultiSelect($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addMultiSelectSelectAllField(string $name, array $options = []): void
    {
        $this->addMultiSelectSelectAll($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addChoiceRadiosField(string $name, array $options = []): void
    {
        $this->addChoiceRadios($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $options */
    protected function addChoiceCheckboxesField(string $name, array $options = []): void
    {
        $this->addChoiceCheckboxes($this->boundBuilder(), $name, $options);
    }

    /** @param array<string, mixed> $fieldConfiguration */
    protected function addSwitchField(string $fieldName, array $fieldConfiguration = []): void
    {
        $this->addSwitchType($this->boundBuilder(), $fieldName, $fieldConfiguration);
    }

    /** @param array<string, mixed> $fieldConfiguration */
    protected function addJsonField(string $fieldName, array $fieldConfiguration = []): void
    {
        $this->addJsonType($this->boundBuilder(), $fieldName, $fieldConfiguration);
    }

    /** @param array<string, mixed> $fieldConfiguration */
    protected function addBoolField(string $fieldName, array $fieldConfiguration = []): void
    {
        $this->addBoolType($this->boundBuilder(), $fieldName, $fieldConfiguration);
    }

    /** @param array<string, mixed> $fieldConfiguration */
    protected function addMoneyField(string $fieldName, array $fieldConfiguration = []): void
    {
        $this->addMoneyType($this->boundBuilder(), $fieldName, $fieldConfiguration);
    }

    /** @param array<string, mixed> $fieldConfiguration */
    protected function addCsvField(string $fieldName, array $fieldConfiguration = []): void
    {
        $this->addCsvType($this->boundBuilder(), $fieldName, $fieldConfiguration);
    }

    /**
     * Native &lt;select&gt; (single): ChoiceType collapsed, not multiple.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options merged after defaults (choices required in practice)
     */
    protected function addSelect(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $options = array_merge([
            'expanded' => false,
            'multiple' => false,
        ], $options);
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    /**
     * Native &lt;select multiple&gt;: ChoiceType collapsed + multiple.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addMultiSelect(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $options = array_merge([
            'expanded' => false,
            'multiple' => true,
        ], $options);
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    /**
     * Same as {@see addMultiSelect()} but enables "Select all" (collapsed or checkboxes)
     * when **nowo-tech/select-all-choice-bundle** is installed (`select_all` option).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the Select All Choice bundle is not installed
     */
    protected function addMultiSelectSelectAll(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(NowoSelectAllChoiceBundle::class)) {
            throw new LogicException('addMultiSelectSelectAll() requires nowo-tech/select-all-choice-bundle. Install it or use addMultiSelect() and pass select_all in options manually after installing the bundle.');
        }

        $options = array_merge([
            'expanded'   => false,
            'multiple'   => true,
            'select_all' => true,
        ], $options);
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    /**
     * Radio list: ChoiceType expanded, not multiple.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoiceRadios(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $options = array_merge([
            'expanded'    => true,
            'multiple'    => false,
            'placeholder' => false,
            'attr'        => ['class' => ''],
        ], $options);
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    /**
     * Checkbox group (multiple): ChoiceType expanded + multiple.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoiceCheckboxes(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $options = array_merge([
            'expanded'    => true,
            'multiple'    => true,
            'placeholder' => false,
            'attr'        => ['class' => ''],
        ], $options);
        $this->addWithDefaults($builder, $name, ChoiceType::class, $options);
    }

    /**
     * Adds any form type by FQCN with Form Kit merged options (labels, attr, etc.).
     * Typical use: Symfony UX Autocomplete field class extending BaseEntityAutocompleteType.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param class-string<FormTypeInterface<mixed>> $formTypeFqcn
     * @param array<string, mixed> $options
     */
    protected function addAutocompleteField(FormBuilderInterface $builder, string $name, string $formTypeFqcn, array $options = []): void
    {
        $this->addWithDefaults($builder, $name, $formTypeFqcn, $options);
    }

    /**
     * Adds a rich-text field using FOSCKEditorBundle's `CKEditorType` (CKEditor 4).
     *
     * Requires the optional Composer package `friendsofsymfony/ckeditor-bundle`. Pass CKEditor-specific
     * options (e.g. `config_name`, `config`) in `$options`; label, placeholder, and help follow Form Kit conventions.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options Field options merged via FormOptionsMerger
     *
     * @throws LogicException when the CKEditor bundle class is not available (package not installed)
     */
    protected function addCKEditorField(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(CKEditorType::class)) {
            throw new LogicException('addCKEditorField() requires friendsofsymfony/ckeditor-bundle. Install it and run bin/console ckeditor:install.');
        }

        $this->addWithDefaults($builder, $name, CKEditorType::class, $options);
    }

    /**
     * Adds a Symfony UX Dropzone field (`DropzoneType`) with Form Kit merged options.
     *
     * Requires the optional Composer package `symfony/ux-dropzone`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when `symfony/ux-dropzone` is not installed
     */
    protected function addDropzone(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(DropzoneType::class)) {
            throw new LogicException('addDropzone() requires symfony/ux-dropzone. Install it or use addWithDefaults() with your own upload type.');
        }

        $this->addWithDefaults($builder, $name, DropzoneType::class, $options);
    }

    /**
     * Like {@see addDropzone()} using the builder from {@see withBuilder()}.
     *
     * @param array<string, mixed> $options
     */
    protected function addDropzoneField(string $name, array $options = []): void
    {
        $this->addDropzone($this->boundBuilder(), $name, $options);
    }

    /**
     * Adds a Symfony UX Cropper.js field (`CropperType`) with Form Kit merged options.
     *
     * Requires `symfony/ux-cropperjs`. Pass Cropper-specific options such as `public_url`
     * (and typically inject `CropperInterface` in the controller to build crop options).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when `symfony/ux-cropperjs` is not installed
     */
    protected function addCropper(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(CropperType::class)) {
            throw new LogicException('addCropper() requires symfony/ux-cropperjs. Install it or use addWithDefaults() with CropperType when available.');
        }

        $this->addWithDefaults($builder, $name, CropperType::class, $options);
    }

    /**
     * Like {@see addCropper()} using the builder from {@see withBuilder()}.
     *
     * @param array<string, mixed> $options
     */
    protected function addCropperField(string $name, array $options = []): void
    {
        $this->addCropper($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech OTP digits field. Requires `nowo-tech/otp-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addOtp(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, OtpType::class, 'nowo-tech/otp-input-bundle', 'addOtp', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addOtpField(string $name, array $options = []): void
    {
        $this->addOtp($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech international phone field. Requires `nowo-tech/phone-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addPhone(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, PhoneType::class, 'nowo-tech/phone-input-bundle', 'addPhone', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPhoneField(string $name, array $options = []): void
    {
        $this->addPhone($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech show/hide password field. Requires `nowo-tech/password-toggle-bundle`.
     *
     * Distinct from {@see addPassword()} (Symfony `PasswordType`).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addPasswordToggle(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, PasswordToggleType::class, 'nowo-tech/password-toggle-bundle', 'addPasswordToggle', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPasswordToggleField(string $name, array $options = []): void
    {
        $this->addPasswordToggle($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech password strength meter. Requires `nowo-tech/password-strength-bundle`.
     *
     * Pass `use_password_toggle => true` to combine with PasswordToggleBundle when that package is also installed.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addPasswordStrength(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, PasswordStrengthType::class, 'nowo-tech/password-strength-bundle', 'addPasswordStrength', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPasswordStrengthField(string $name, array $options = []): void
    {
        $this->addPasswordStrength($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech icon picker. Requires `nowo-tech/icon-selector-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addIconSelector(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, IconSelectorType::class, 'nowo-tech/icon-selector-bundle', 'addIconSelector', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addIconSelectorField(string $name, array $options = []): void
    {
        $this->addIconSelector($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech CKEditor 5 field. Requires `nowo-tech/ckeditor5-editor-bundle`.
     *
     * Distinct from {@see addCKEditorField()} (FOSCKEditorBundle / CKEditor 4).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addCkeditor5Editor(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, Ckeditor5EditorType::class, 'nowo-tech/ckeditor5-editor-bundle', 'addCkeditor5Editor', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCkeditor5EditorField(string $name, array $options = []): void
    {
        $this->addCkeditor5Editor($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech Tiptap editor. Requires `nowo-tech/tiptap-editor-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addTiptapEditor(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, TiptapEditorType::class, 'nowo-tech/tiptap-editor-bundle', 'addTiptapEditor', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTiptapEditorField(string $name, array $options = []): void
    {
        $this->addTiptapEditor($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech Tagify tags field. Requires `nowo-tech/tag-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addTagInput(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, TagType::class, 'nowo-tech/tag-input-bundle', 'addTagInput', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTagInputField(string $name, array $options = []): void
    {
        $this->addTagInput($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech slide-to-confirm control. Requires `nowo-tech/slide-to-confirm-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addSlideToConfirm(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalSuggestedType($builder, $name, SlideToConfirmType::class, 'nowo-tech/slide-to-confirm-bundle', 'addSlideToConfirm', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addSlideToConfirmField(string $name, array $options = []): void
    {
        $this->addSlideToConfirm($this->boundBuilder(), $name, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $options
     *
     * @throws LogicException when `$type` is not available
     */
    private function addOptionalSuggestedType(
        FormBuilderInterface $builder,
        string $name,
        string $type,
        string $package,
        string $helper,
        array $options,
    ): void {
        if (!class_exists($type)) {
            throw new LogicException(sprintf('%s() requires %s. Install it or use addWithDefaults() with the type FQCN when available.', $helper, $package));
        }

        $this->addWithDefaults($builder, $name, $type, $options);
    }

    /**
     * Minimal switch preset: creates a ChoiceType configured as expanded+multiple and
     * installs a SwitchModelTransformer so the model stays scalar (1/0 or true/false).
     *
     * Expected use:
     * - Model value: int/bool|null (1/0 or true/false)
     * - View value inside ChoiceType: array containing switchValue (ChoiceType expanded+multiple)
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addSwitchType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
    ): void {
        $formName    = $this->getBlockPrefix();
        $switchValue = (int) ($fieldConfiguration['switch_value'] ?? 1);
        $options     = $this->fieldSwitchConfiguration($fieldName, $fieldConfiguration, $formName, $switchValue);

        // Resolve options via merger so translation_domain/label/help conventions are applied.
        $merged = $this->formOptionsMerger->resolve(
            $formName,
            $fieldName,
            ChoiceType::class,
            $options,
            $this->resolvedFormKitConfigName(),
        );

        // If choices contain translation keys, use the field translation_domain by default.
        if (!array_key_exists('choice_translation_domain', $merged) && array_key_exists('translation_domain', $merged)) {
            $merged['choice_translation_domain'] = $merged['translation_domain'];
        }

        $builder->add($fieldName, ChoiceType::class, $merged);

        $builder->get($fieldName)->addModelTransformer(new SwitchModelTransformer($switchValue));
    }

    /**
     * Builds ChoiceType options for a switch field (expanded + multiple).
     *
     * Important:
     * - Removes bundle-specific keys like `label_position` and `switch_value`.
     * - Does NOT apply label/placeholder/help conventions: those are handled by FormOptionsMerger.
     *
     * @param array<string, mixed> $fieldConfiguration
     *
     * @return array<string, mixed>
     */
    private function fieldSwitchConfiguration(string $fieldName, array $fieldConfiguration, string $formName, int $switchValue): array
    {
        $labelPosition = $fieldConfiguration['label_position'] ?? 'horizontal';

        // Build default label key following FormOptionsMerger conventions.
        $fieldNameSnake = $this->camelCaseToSnakeCase($fieldName);
        $labelKey       = $formName . '.' . $fieldNameSnake . '.label';

        // Merge row/attr classes by concatenation (FormOptionsMerger does not concatenate scalars).
        $existingRowAttr   = $fieldConfiguration['row_attr'] ?? [];
        $existingAttr      = $fieldConfiguration['attr'] ?? [];
        $existingLabelAttr = $fieldConfiguration['label_attr'] ?? [];

        $rowAttrBase   = ['class' => 'pt-1', 'style' => 'top:8px'];
        $attrBase      = ['class' => 'form-check form-switch ms-2 ps-0'];
        $labelAttrBase = ['class' => 'form-label'];

        if ($labelPosition === 'horizontal') {
            $fieldConfiguration['label'] ??= false;

            if (!array_key_exists('choices', $fieldConfiguration)) {
                $fieldConfiguration['choices'] = [$labelKey => $switchValue];
            }
        }

        if ($labelPosition === 'vertical') {
            $rowAttrBase = ['class' => 'd-flex flex-column'];

            // Same semantics as horizontal layout: hide the choice label and use a single active choice.
            $fieldConfiguration['choice_label'] ??= false;

            if (!array_key_exists('choices', $fieldConfiguration)) {
                $fieldConfiguration['choices'] = ['active' => $switchValue];
            }
        }

        // Concatenate classes.
        $rowClass   = trim(($existingRowAttr['class'] ?? '') . ' ' . $rowAttrBase['class']);
        $attrClass  = trim(($existingAttr['class'] ?? '') . ' ' . $attrBase['class']);
        $labelClass = trim(($existingLabelAttr['class'] ?? '') . ' ' . $labelAttrBase['class']);

        $fieldConfiguration['row_attr']          = array_merge($rowAttrBase, $existingRowAttr);
        $fieldConfiguration['row_attr']['class'] = $rowClass;

        $fieldConfiguration['attr']          = array_merge($attrBase, $existingAttr);
        $fieldConfiguration['attr']['class'] = $attrClass;

        $fieldConfiguration['label_attr']          = array_merge($labelAttrBase, $existingLabelAttr);
        $fieldConfiguration['label_attr']['class'] = $labelClass;

        // switch always: expanded + multiple, label_position is only for preset choice/row behavior.
        $fieldConfiguration['expanded'] ??= true;
        $fieldConfiguration['multiple'] ??= true;

        unset($fieldConfiguration['label_position'], $fieldConfiguration['switch_value']);

        return $fieldConfiguration;
    }

    private function camelCaseToSnakeCase(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_\\0', lcfirst($name)));
    }

    /**
     * Optional helper if you add switch fields dynamically in events with FormInterface.
     *
     * For regular buildForm usage, prefer addSwitchType() which installs the transformer automatically.
     *
     * @param FormBuilderInterface<mixed>|FormInterface<mixed> $formOrBuilder
     */
    protected function dataTransformerSwitchConfiguration(FormBuilderInterface|FormInterface $formOrBuilder, string $fieldName, int $switchValue = 1): void
    {
        $transformer = new SwitchModelTransformer($switchValue);

        if ($formOrBuilder instanceof FormBuilderInterface) {
            $formOrBuilder->get($fieldName)->addModelTransformer($transformer);

            return;
        }

        $form    = $formOrBuilder;
        $child   = $form->get($fieldName);
        $options = $child->getConfig()->getOptions();

        $form->remove($fieldName);

        $childBuilder = $form->getConfig()->getFormFactory()->createNamedBuilder($fieldName, ChoiceType::class, null, $options);
        $childBuilder->addModelTransformer($transformer);
        $form->add($childBuilder->getForm());
    }

    /**
     * JSON textarea preset: textarea widget + JsonModelTransformer.
     *
     * Model:
     * - array/object/null
     *
     * View:
     * - JSON string (pretty by default)
     *
     * FieldConfiguration options:
     * - json_pretty: bool (default true)
     * - json_unescaped_unicode: bool (default true)
     *
     * Any other keys are passed to TextareaType (and merged by conventions via FormOptionsMerger).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addJsonType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
    ): void {
        $formName = $this->getBlockPrefix();

        $prettyPrint      = (bool) ($fieldConfiguration['json_pretty'] ?? true);
        $unescapedUnicode = (bool) ($fieldConfiguration['json_unescaped_unicode'] ?? true);

        unset($fieldConfiguration['json_pretty'], $fieldConfiguration['json_unescaped_unicode']);

        $merged = $this->formOptionsMerger->resolve(
            $formName,
            $fieldName,
            TextareaType::class,
            $fieldConfiguration,
            $this->resolvedFormKitConfigName(),
        );

        $builder->add($fieldName, TextareaType::class, $merged);
        $builder->get($fieldName)->addModelTransformer(new JsonModelTransformer($prettyPrint, $unescapedUnicode));
    }

    /**
     * Checkbox preset with boolean/int scalar transformer.
     *
     * Model:
     * - int 1/0 (or "1"/"0") or true/false/null
     *
     * View:
     * - boolean for CheckboxType
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addBoolType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
    ): void {
        $formName = $this->getBlockPrefix();

        $onValue  = (int) ($fieldConfiguration['on_value'] ?? 1);
        $offValue = (int) ($fieldConfiguration['off_value'] ?? 0);

        unset($fieldConfiguration['on_value'], $fieldConfiguration['off_value']);

        $merged = $this->formOptionsMerger->resolve(
            $formName,
            $fieldName,
            CheckboxType::class,
            $fieldConfiguration,
            $this->resolvedFormKitConfigName(),
        );

        $builder->add($fieldName, CheckboxType::class, $merged);
        $builder->get($fieldName)->addModelTransformer(new BoolModelTransformer($onValue, $offValue));
    }

    /**
     * Money preset: text input with cents <-> decimal string transformer.
     *
     * Model:
     * - int cents (e.g. 1234 => "12.34")
     *
     * View:
     * - decimal string (supports comma as decimal separator)
     *
     * FieldConfiguration:
     * - money_scale: int (default 2)
     *
     * Defaults `required` to false. After merge, placeholder is stripped from root and from `attr` (merger copies it there).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addMoneyType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
    ): void {
        $formName = $this->getBlockPrefix();

        $fieldConfiguration = array_merge(['required' => false], $fieldConfiguration);

        $scale = (int) ($fieldConfiguration['money_scale'] ?? 2);
        unset($fieldConfiguration['money_scale']);

        $merged = $this->formOptionsMerger->resolve(
            $formName,
            $fieldName,
            TextType::class,
            $fieldConfiguration,
            $this->resolvedFormKitConfigName(),
        );

        $merged = FormFieldOptionsHelper::stripPlaceholderFromMergedOptions($merged);

        $builder->add($fieldName, TextType::class, $merged);
        $builder->get($fieldName)->addModelTransformer(new MoneyModelTransformer($scale));
    }

    /**
     * CSV preset: textarea with array<string> <-> CSV string transformer.
     *
     * Model:
     * - list<string>|null
     *
     * View:
     * - CSV string like "a,b,c"
     *
     * FieldConfiguration:
     * - csv_separator: string (default ",")
     * - csv_trim_tokens: bool (default true)
     * - csv_allow_empty_tokens: bool (default false)
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addCsvType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
    ): void {
        $formName = $this->getBlockPrefix();

        $separator        = (string) ($fieldConfiguration['csv_separator'] ?? ',');
        $trimTokens       = (bool) ($fieldConfiguration['csv_trim_tokens'] ?? true);
        $allowEmptyTokens = (bool) ($fieldConfiguration['csv_allow_empty_tokens'] ?? false);

        unset($fieldConfiguration['csv_separator'], $fieldConfiguration['csv_trim_tokens'], $fieldConfiguration['csv_allow_empty_tokens']);

        $merged = $this->formOptionsMerger->resolve(
            $formName,
            $fieldName,
            TextareaType::class,
            $fieldConfiguration,
            $this->resolvedFormKitConfigName(),
        );

        $builder->add($fieldName, TextareaType::class, $merged);
        $builder->get($fieldName)->addModelTransformer(new CsvModelTransformer($separator, $trimTokens, $allowEmptyTokens));
    }
}
