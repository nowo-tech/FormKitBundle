<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use InvalidArgumentException;
use LogicException;
use Nowo\Ckeditor5EditorBundle\Form\Ckeditor5EditorType;
use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\IconSelectorBundle\Form\IconSelectorType;
use Nowo\OtpInputBundle\Form\OtpType;
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordToggleBundle\Form\Type\PasswordType as PasswordToggleType;
use Nowo\PhoneInputBundle\Form\Type\PhoneType;
use Nowo\SlideToConfirmBundle\Form\Type\SlideToConfirmType;
use Nowo\TagInputBundle\Form\TagType;
use Nowo\TiptapEditorBundle\Form\TiptapEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

use function class_exists;
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

    /** @var FormBuilderInterface<mixed>|null Builder bound by {@see withBuilder()}; used by add*Field() helpers. */
    private ?FormBuilderInterface $formKitBoundBuilder = null;

    /** Profile name (key in nowo_form_kit.profiles) to use; null = default_profile */
    private ?string $formKitConfigName = null;

    /** True after setFormKitConfigName() or after resolving #[FormKitConfig]. */
    private bool $formKitConfigNameResolved = false;

    public function setFormOptionsMerger(FormOptionsMerger $merger): void
    {
        $this->formOptionsMerger = $merger;
    }

    public function setFormTypeMap(FormTypeMap $map): void
    {
        $this->formTypeMap = $map;
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
            $this->resolvedFormKitConfigName(),
        );
    }

    /**
     * Add a field by snake_case type name (must exist in type map). Options are merged in cascade.
     *
     * @param FormBuilderInterface<mixed> $builder
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
        /** @var class-string<FormTypeInterface<mixed>> $typeFqcn */
        $typeFqcn = $fqcn;
        $builder->add($name, $typeFqcn, $this->mergeFieldOptions($name, $typeSnakeCase, $options));
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
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, array{type?: string, ...}|string> $fields e.g. ['full_name' => 'text', 'topic' => ['type' => 'choice', 'choices' => [...]]]
     */
    protected function buildFormFromArray(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $name => $definition) {
            if (is_string($definition)) {
                $this->addField($builder, $name, $definition, []);
            } else {
                $type = $definition['type'] ?? null;
                if (!is_string($type) || $type === '') {
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
     * @param array<string, array{type?: string, ...}|string> $fields
     */
    protected function buildFieldsFromArray(array $fields): void
    {
        $this->buildFormFromArray($this->boundBuilder(), $fields);
    }

    // --- Phase 2: add-by-type helpers (no type class needed, only field name + options) ---

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addText(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'text', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addEmail(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'email', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addTextarea(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'textarea', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addPassword(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'password', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addUrl(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'url', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addInteger(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'integer', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addNumber(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'number', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    protected function addCheckbox(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addField($builder, $name, 'checkbox', $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
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

    /**
     * Optional UX Dropzone (`dropzone` in FormTypeMap). Requires `symfony/ux-dropzone`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addDropzone(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(DropzoneType::class)) {
            throw new LogicException('addDropzone() requires symfony/ux-dropzone. Install it or register a custom type in nowo_form_kit.type_map.');
        }

        $this->addField($builder, $name, 'dropzone', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addDropzoneField(string $name, array $options = []): void
    {
        $this->addDropzone($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional UX Cropper.js (`cropper` in FormTypeMap). Requires `symfony/ux-cropperjs`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addCropper(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        if (!class_exists(CropperType::class)) {
            throw new LogicException('addCropper() requires symfony/ux-cropperjs. Install it or register a custom type in nowo_form_kit.type_map.');
        }

        $this->addField($builder, $name, 'cropper', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCropperField(string $name, array $options = []): void
    {
        $this->addCropper($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech OTP (`otp` in FormTypeMap). Requires `nowo-tech/otp-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addOtp(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'otp', OtpType::class, 'nowo-tech/otp-input-bundle', 'addOtp', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addOtpField(string $name, array $options = []): void
    {
        $this->addOtp($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech phone (`phone` in FormTypeMap). Requires `nowo-tech/phone-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addPhone(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'phone', PhoneType::class, 'nowo-tech/phone-input-bundle', 'addPhone', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPhoneField(string $name, array $options = []): void
    {
        $this->addPhone($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech password toggle (`password_toggle` in FormTypeMap).
     * Requires `nowo-tech/password-toggle-bundle`. Distinct from {@see addPassword()}.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addPasswordToggle(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'password_toggle', PasswordToggleType::class, 'nowo-tech/password-toggle-bundle', 'addPasswordToggle', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPasswordToggleField(string $name, array $options = []): void
    {
        $this->addPasswordToggle($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech password strength (`password_strength` in FormTypeMap).
     * Requires `nowo-tech/password-strength-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addPasswordStrength(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'password_strength', PasswordStrengthType::class, 'nowo-tech/password-strength-bundle', 'addPasswordStrength', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addPasswordStrengthField(string $name, array $options = []): void
    {
        $this->addPasswordStrength($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech icon selector (`icon_selector` in FormTypeMap).
     * Requires `nowo-tech/icon-selector-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addIconSelector(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'icon_selector', IconSelectorType::class, 'nowo-tech/icon-selector-bundle', 'addIconSelector', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addIconSelectorField(string $name, array $options = []): void
    {
        $this->addIconSelector($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech CKEditor 5 (`ckeditor5` in FormTypeMap).
     * Requires `nowo-tech/ckeditor5-editor-bundle`. Distinct from FOSCKEditorBundle (CKEditor 4).
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addCkeditor5Editor(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'ckeditor5', Ckeditor5EditorType::class, 'nowo-tech/ckeditor5-editor-bundle', 'addCkeditor5Editor', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addCkeditor5EditorField(string $name, array $options = []): void
    {
        $this->addCkeditor5Editor($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech Tiptap editor (`tiptap` in FormTypeMap).
     * Requires `nowo-tech/tiptap-editor-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addTiptapEditor(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'tiptap', TiptapEditorType::class, 'nowo-tech/tiptap-editor-bundle', 'addTiptapEditor', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTiptapEditorField(string $name, array $options = []): void
    {
        $this->addTiptapEditor($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech Tagify field (`tag` in FormTypeMap). Requires `nowo-tech/tag-input-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addTagInput(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'tag', TagType::class, 'nowo-tech/tag-input-bundle', 'addTagInput', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addTagInputField(string $name, array $options = []): void
    {
        $this->addTagInput($this->boundBuilder(), $name, $options);
    }

    /**
     * Optional nowo-tech slide-to-confirm (`slide_to_confirm` in FormTypeMap).
     * Requires `nowo-tech/slide-to-confirm-bundle`.
     *
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     *
     * @throws LogicException when the package is not installed / type not in the map
     */
    protected function addSlideToConfirm(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addOptionalMappedField($builder, $name, 'slide_to_confirm', SlideToConfirmType::class, 'nowo-tech/slide-to-confirm-bundle', 'addSlideToConfirm', $options);
    }

    /** @param array<string, mixed> $options */
    protected function addSlideToConfirmField(string $name, array $options = []): void
    {
        $this->addSlideToConfirm($this->boundBuilder(), $name, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param class-string<FormTypeInterface<mixed>> $typeFqcn
     * @param array<string, mixed> $options
     *
     * @throws LogicException when `$typeFqcn` is not available
     */
    private function addOptionalMappedField(
        FormBuilderInterface $builder,
        string $name,
        string $snakeCase,
        string $typeFqcn,
        string $package,
        string $helper,
        array $options,
    ): void {
        if (!class_exists($typeFqcn)) {
            throw new LogicException(sprintf('%s() requires %s. Install it or register a custom type in nowo_form_kit.type_map.', $helper, $package));
        }

        $this->addField($builder, $name, $snakeCase, $options);
    }
}
