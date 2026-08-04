<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Controller;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use InvalidArgumentException;
use LogicException;
use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use Nowo\FormKitBundle\Form\FormFieldOptionsHelper;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\StaticHtmlType;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
use Nowo\SelectAllChoiceBundle\NowoSelectAllChoiceBundle;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

use function array_key_exists;
use function class_exists;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;
use function str_contains;

/**
 * Controller-side helpers to build Symfony forms faster using Form Kit conventions.
 *
 * Usage (controllers):
 * - Inject and call setFormOptionsMerger() and setFormTypeMap() (or do constructor injection)
 * - Optionally call setFormKitFormName('my_form_name') once, OR pass $formName per call
 * - Call addTextType()/addEmailType()/... which will resolve FQCNs via FormTypeMap and
 *   merge final options via FormOptionsMerger (defaults + conventions from YAML).
 *
 * Notes:
 * - This trait intentionally does NOT support legacy "parent_attr"; only row_attr/attr are used.
 * - The trait can also be used inside Symfony FormTypes; if $formName is not provided and
 *   the consuming class has getBlockPrefix(), that value will be used.
 */
trait FormKitControllerTrait
{
    /**
     * Unique property name to avoid collisions with controllers that declare
     * their own `$formOptionsMerger` via constructor property promotion.
     */
    private ?FormOptionsMerger $formKitOptionsMerger = null;

    /** Unique property name to avoid collisions with controller properties. */
    private ?FormTypeMap $formKitTypeMap = null;

    /**
     * Key in nowo_form_kit.profiles. When null, FormOptionsMerger will use default_profile.
     */
    private ?string $formKitConfigName = null;

    /**
     * When set, add* methods will use this form name for convention keys.
     */
    private ?string $formKitFormName = null;

    /** @var callable|null */
    private $formKitTranslationsLocaleResolver;

    /** @var array<string, mixed> */
    private array $formKitTranslationsDefaults = [];

    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        $this->formKitOptionsMerger = $formOptionsMerger;
    }

    public function setFormTypeMap(FormTypeMap $formTypeMap): void
    {
        $this->formKitTypeMap = $formTypeMap;
    }

    public function setFormKitConfigName(?string $configName): void
    {
        $this->formKitConfigName = $configName;
    }

    public function setFormKitFormName(?string $formName): void
    {
        $this->formKitFormName = $formName;
    }

    /** @param callable|null $resolver */
    public function setFormKitTranslationsLocaleResolver($resolver): void
    {
        $this->formKitTranslationsLocaleResolver = $resolver;
    }

    /** @param array<string, mixed> $defaults */
    public function setFormKitTranslationsDefaults(array $defaults): void
    {
        $this->formKitTranslationsDefaults = $defaults;
    }

    /**
     * Adds a field with merged options.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options field-specific options to override/extend defaults
     *
     * @throws InvalidArgumentException when dependencies are missing or type cannot be resolved
     */
    protected function addFieldType(
        FormBuilderInterface $builder,
        string $fieldName,
        string $type,
        array $options = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $merger             = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');
        $resolvedFormName   = $this->resolveFormName($formName);
        $resolvedTypeFqcn   = $this->resolveTypeFqcn($type);
        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            $resolvedTypeFqcn,
            $options,
            $resolvedConfigName,
        );

        $builder->add($fieldName, $resolvedTypeFqcn, $mergedOptions);
    }

    /**
     * @see FormOptionsTrait::mergeSubFormFieldOptions()
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
     * @see FormOptionsTrait::removeFieldOptionKeys()
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
     * @param array<string, mixed> $options Extra field options merged with defaults
     */
    protected function addFieldBreak(
        FormBuilderInterface $builder,
        ?string $fieldName = null,
        ?string $html = null,
        array $options = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $name = $fieldName ?? ('field_break_' . bin2hex(random_bytes(4)));
        $base = [
            'label'       => false,
            'placeholder' => false,
            'help'        => false,
            'html'        => $html ?? '<div class="w-100"></div>',
        ];
        $this->addFieldType($builder, $name, StaticHtmlType::class, array_merge($base, $options), $configName, $formName);
    }

    /**
     * Controller-side "Phase 2" helpers (snake_case names resolved via FormTypeMap).
     *
     * These methods do NOT require importing Symfony Form Types.
     */
    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addTextType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'text', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addEmailType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'email', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addTextareaType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'textarea', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addPasswordType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'password', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addUrlType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'url', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addIntegerType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'integer', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addNumberType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'number', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addCheckboxType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'checkbox', $options, $configName, $formName);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoiceType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * Native &lt;select&gt; single — same defaults as {@see FormOptionsTrait::addSelect()}.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addSelectType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $options = array_merge([
            'expanded' => false,
            'multiple' => false,
        ], $options);
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * Native &lt;select multiple&gt;.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addMultiSelectType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $options = array_merge([
            'expanded' => false,
            'multiple' => true,
        ], $options);
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * @see FormOptionsTrait::addMultiSelectSelectAll()
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addMultiSelectSelectAllType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        if (!class_exists(NowoSelectAllChoiceBundle::class)) {
            throw new LogicException('addMultiSelectSelectAllType() requires nowo-tech/select-all-choice-bundle. Use addMultiSelectType() or install the bundle.');
        }

        $options = array_merge([
            'expanded'   => false,
            'multiple'   => true,
            'select_all' => true,
        ], $options);
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * Radio list.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoiceRadiosType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $options = array_merge([
            'expanded' => true,
            'multiple' => false,
        ], $options);
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * Checkbox group (multiple choices).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addChoiceCheckboxesType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $options = array_merge([
            'expanded' => true,
            'multiple' => true,
        ], $options);
        $this->addFieldType($builder, $fieldName, 'choice', $options, $configName, $formName);
    }

    /**
     * Any FormType FQCN with merged options (e.g. Symfony UX Autocomplete field class).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param class-string<FormTypeInterface<mixed>> $formTypeFqcn
     * @param array<string, mixed> $options
     */
    protected function addAutocompleteFieldType(FormBuilderInterface $builder, string $fieldName, string $formTypeFqcn, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        $this->addFieldType($builder, $fieldName, $formTypeFqcn, $options, $configName, $formName);
    }

    /**
     * Adds a rich-text field using FOSCKEditorBundle's `CKEditorType`, with merged options from the controller context.
     *
     * Same behaviour as `FormOptionsTrait::addCKEditorField()` but resolves the form name and type map like other `add*Type` helpers.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when `friendsofsymfony/ckeditor-bundle` is not installed
     */
    protected function addCKEditorFieldType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        if (!class_exists(CKEditorType::class)) {
            throw new LogicException('addCKEditorFieldType() requires friendsofsymfony/ckeditor-bundle. Install it and run bin/console ckeditor:install.');
        }

        $this->addFieldType($builder, $fieldName, CKEditorType::class, $options, $configName, $formName);
    }

    /**
     * Symfony UX Dropzone with merged Form Kit options. Requires `symfony/ux-dropzone`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addDropzoneFieldType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        if (!class_exists(DropzoneType::class)) {
            throw new LogicException('addDropzoneFieldType() requires symfony/ux-dropzone.');
        }

        $this->addFieldType($builder, $fieldName, DropzoneType::class, $options, $configName, $formName);
    }

    /**
     * Symfony UX Cropper.js with merged Form Kit options. Requires `symfony/ux-cropperjs`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed
     */
    protected function addCropperFieldType(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
    {
        if (!class_exists(CropperType::class)) {
            throw new LogicException('addCropperFieldType() requires symfony/ux-cropperjs.');
        }

        $this->addFieldType($builder, $fieldName, CropperType::class, $options, $configName, $formName);
    }

    /**
     * Adds A2lix translations field with defaults and optional locale resolver.
     *
     * @param FormBuilderInterface<mixed>|FormInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addTranslations(
        FormBuilderInterface|FormInterface $builder,
        array $options,
    ): FormBuilderInterface|FormInterface {
        if (!array_key_exists('form_type', $options) || !is_string($options['form_type']) || $options['form_type'] === '') {
            throw new InvalidArgumentException('The "form_type" key is required in addTranslations() options.');
        }

        $localeContext   = $this->resolveTranslationsLocaleContext($options);
        $defaultLocale   = $localeContext['default_locale'] ?? 'en';
        $enabledLocales  = $localeContext['enabled_locales'] ?? [$defaultLocale];
        $requiredLocales = $localeContext['required_locales'] ?? [$defaultLocale];

        $defaultOptions = array_merge([
            'label'            => false,
            'enabled_locales'  => $enabledLocales,
            'default_locale'   => $defaultLocale,
            'required_locales' => $requiredLocales,
            'row_attr'         => ['class' => 'col-12 mb-1'],
            'form_options'     => [],
            'data_class'       => null,
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

        $finalOptions['form_options']['row_attr']['class'] = trim(($finalOptions['form_options']['row_attr']['class'] ?? '') . ' row');
        $finalOptions['form_options']['attr']['class']     = trim(($finalOptions['form_options']['attr']['class'] ?? '') . ' row');

        return $builder->add('translations', TranslationsFormsType::class, $finalOptions);
    }

    /**
     * Adds a switch field (ChoiceType expanded+multiple) with a model transformer
     * so the model stays scalar (1/0 or true/false).
     *
     * Switch preset keys:
     * - label_position: 'horizontal' (default) or 'vertical'
     * - switch_value: int on-value (default 1)
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addSwitchType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $resolvedFormName = $this->resolveFormName($formName);
        $switchValue      = (int) ($fieldConfiguration['switch_value'] ?? 1);
        $labelPosition    = $fieldConfiguration['label_position'] ?? 'horizontal';

        $mergedSwitchOptions = $this->fieldSwitchConfiguration($fieldName, $fieldConfiguration, $resolvedFormName, $switchValue, $labelPosition);

        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $merger = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            ChoiceType::class,
            $mergedSwitchOptions,
            $resolvedConfigName,
        );

        if (!array_key_exists('choice_translation_domain', $mergedOptions) && array_key_exists('translation_domain', $mergedOptions)) {
            $mergedOptions['choice_translation_domain'] = $mergedOptions['translation_domain'];
        }

        $builder->add($fieldName, ChoiceType::class, $mergedOptions);
        $builder->get($fieldName)->addModelTransformer(new SwitchModelTransformer($switchValue));
    }

    /**
     * JSON textarea preset: textarea widget + JsonModelTransformer.
     *
     * FieldConfiguration options:
     * - json_pretty: bool (default true)
     * - json_unescaped_unicode: bool (default true)
     *
     * Any other keys are merged via FormOptionsMerger for TextareaType.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addJsonType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $resolvedFormName = $this->resolveFormName($formName);

        $prettyPrint      = (bool) ($fieldConfiguration['json_pretty'] ?? true);
        $unescapedUnicode = (bool) ($fieldConfiguration['json_unescaped_unicode'] ?? true);

        unset($fieldConfiguration['json_pretty'], $fieldConfiguration['json_unescaped_unicode']);

        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $textareaType = TextareaType::class;

        $merger = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            $textareaType,
            $fieldConfiguration,
            $resolvedConfigName,
        );

        $builder->add($fieldName, $textareaType, $mergedOptions);
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
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $resolvedFormName = $this->resolveFormName($formName);

        $onValue  = (int) ($fieldConfiguration['on_value'] ?? 1);
        $offValue = (int) ($fieldConfiguration['off_value'] ?? 0);

        unset($fieldConfiguration['on_value'], $fieldConfiguration['off_value']);

        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $checkboxType = CheckboxType::class;
        $merger       = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            $checkboxType,
            $fieldConfiguration,
            $resolvedConfigName,
        );

        $builder->add($fieldName, $checkboxType, $mergedOptions);
        $builder->get($fieldName)->addModelTransformer(new BoolModelTransformer($onValue, $offValue));
    }

    /**
     * Money preset: text input with cents <-> decimal string transformer.
     *
     * Defaults `required` to false; strips placeholder from merged options (root and `attr`).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addMoneyType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $resolvedFormName = $this->resolveFormName($formName);

        $fieldConfiguration = array_merge(['required' => false], $fieldConfiguration);

        $scale = (int) ($fieldConfiguration['money_scale'] ?? 2);
        unset($fieldConfiguration['money_scale']);

        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $textType = TextType::class;
        $merger   = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            $textType,
            $fieldConfiguration,
            $resolvedConfigName,
        );

        $mergedOptions = FormFieldOptionsHelper::stripPlaceholderFromMergedOptions($mergedOptions);

        $builder->add($fieldName, $textType, $mergedOptions);
        $builder->get($fieldName)->addModelTransformer(new MoneyModelTransformer($scale));
    }

    /**
     * CSV preset: textarea with array<string> <-> CSV string transformer.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fieldConfiguration
     */
    protected function addCsvType(
        FormBuilderInterface $builder,
        string $fieldName,
        array $fieldConfiguration = [],
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        $resolvedFormName = $this->resolveFormName($formName);

        $separator        = (string) ($fieldConfiguration['csv_separator'] ?? ',');
        $trimTokens       = (bool) ($fieldConfiguration['csv_trim_tokens'] ?? true);
        $allowEmptyTokens = (bool) ($fieldConfiguration['csv_allow_empty_tokens'] ?? false);

        unset(
            $fieldConfiguration['csv_separator'],
            $fieldConfiguration['csv_trim_tokens'],
            $fieldConfiguration['csv_allow_empty_tokens'],
        );

        $resolvedConfigName = $configName ?? $this->formKitConfigName;

        $textareaType = TextareaType::class;
        $merger       = $this->formKitOptionsMerger ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormOptionsMerger().');

        $mergedOptions = $merger->resolve(
            $resolvedFormName,
            $fieldName,
            $textareaType,
            $fieldConfiguration,
            $resolvedConfigName,
        );

        $builder->add($fieldName, $textareaType, $mergedOptions);
        $builder->get($fieldName)->addModelTransformer(new CsvModelTransformer($separator, $trimTokens, $allowEmptyTokens));
    }

    /**
     * @param array<string, mixed> $fieldConfiguration
     *
     * @return array<string, mixed>
     */
    private function fieldSwitchConfiguration(string $fieldName, array $fieldConfiguration, string $formName, int $switchValue, string $labelPosition): array
    {
        $fieldNameSnake = $this->camelCaseToSnakeCase($fieldName);
        $labelKey       = $formName . '.' . $fieldNameSnake . '.label';

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
            $fieldConfiguration['choice_label'] ??= false;
            if (!array_key_exists('choices', $fieldConfiguration)) {
                $fieldConfiguration['choices'] = ['active' => $switchValue];
            }
        }

        $rowClass   = trim(($existingRowAttr['class'] ?? '') . ' ' . $rowAttrBase['class']);
        $attrClass  = trim(($existingAttr['class'] ?? '') . ' ' . $attrBase['class']);
        $labelClass = trim(($existingLabelAttr['class'] ?? '') . ' ' . $labelAttrBase['class']);

        $fieldConfiguration['row_attr']          = array_merge($rowAttrBase, $existingRowAttr);
        $fieldConfiguration['row_attr']['class'] = $rowClass;

        $fieldConfiguration['attr']          = array_merge($attrBase, $existingAttr);
        $fieldConfiguration['attr']['class'] = $attrClass;

        $fieldConfiguration['label_attr']          = array_merge($labelAttrBase, $existingLabelAttr);
        $fieldConfiguration['label_attr']['class'] = $labelClass;

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
     * Build multiple fields from an array definition.
     *
     * Each key is the field name. Value can be:
     * - string: type (FQCN or snake_case, e.g. TextType::class or 'text')
     * - array: ['type' => <FQCN|snake_case>, ...options] (options passed to FormOptionsMerger)
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, array{type?: string, ...}|string> $fields
     *
     * @throws InvalidArgumentException when a field definition is invalid
     */
    protected function buildFormFromArray(
        FormBuilderInterface $builder,
        array $fields,
        ?string $configName = null,
        ?string $formName = null,
    ): void {
        foreach ($fields as $name => $definition) {
            if (is_string($definition)) {
                $this->addFieldType($builder, $name, $definition, [], $configName, $formName);
                continue;
            }

            $type = $definition['type'] ?? null;
            if (!is_string($type) || $type === '') {
                throw new InvalidArgumentException(sprintf('Field "%s" must have a non-empty "type" key.', (string) $name));
            }

            $options = $definition;
            unset($options['type']);

            $this->addFieldType($builder, $name, $type, $options, $configName, $formName);
        }
    }

    /**
     * Resolve form name for convention keys.
     *
     * Order:
     * 1) explicit $formName argument
     * 2) setFormKitFormName() property
     * 3) getBlockPrefix() if available (helps reusing the trait in FormTypes)
     *
     * @throws InvalidArgumentException when no form name can be resolved
     */
    private function resolveFormName(?string $formName): string
    {
        if ($formName !== null && $formName !== '') {
            return $formName;
        }

        if ($this->formKitFormName !== null && $this->formKitFormName !== '') {
            return $this->formKitFormName;
        }

        if (method_exists($this, 'getBlockPrefix')) {
            /** @phpstan-ignore-next-line */
            $blockPrefix = $this->getBlockPrefix();
            if (is_string($blockPrefix) && $blockPrefix !== '') {
                return $blockPrefix;
            }
        }

        throw new InvalidArgumentException('FormKitControllerTrait requires a form name. Call setFormKitFormName() or pass $formName to the add* methods.');
    }

    /**
     * Resolve type to FQCN.
     * - If $type looks like an FQCN (contains backslash) return it.
     * - Otherwise resolve snake_case using FormTypeMap.
     *
     * @throws InvalidArgumentException when FormTypeMap is missing or type cannot be resolved
     *
     * @return class-string<FormTypeInterface<mixed>>
     */
    private function resolveTypeFqcn(string $type): string
    {
        if (str_contains($type, '\\')) {
            if (!class_exists($type) || !is_a($type, FormTypeInterface::class, true)) {
                throw new InvalidArgumentException(sprintf('Unknown form type "%s". Use a form type FQCN or a registered snake_case alias.', $type));
            }

            /* @var class-string<FormTypeInterface<mixed>> $type */
            return $type;
        }

        $typeMap = $this->formKitTypeMap ?? throw new InvalidArgumentException('FormKitControllerTrait requires setFormTypeMap() when using snake_case types.');

        $fqcn = $typeMap->resolve($type);
        if ($fqcn === null) {
            throw new InvalidArgumentException(sprintf('Unknown form type "%s". Register it in nowo_form_kit.type_map or use a built-in type.', $type));
        }

        /* @var class-string<FormTypeInterface<mixed>> $fqcn */
        return $fqcn;
    }
}
