# Usage

## Table of contents

- [Usage strategies](#usage-strategies)
- [FrankenPHP worker mode](#frankenphp-worker-mode)
- [FormOptionsMerger service](#formoptionsmerger-service)
- [Controller strategy](#controller-strategy)
- [Options strategy (FormOptionsTrait)](#options-strategy-formoptionstrait)
- [nowo-tech widgets supported](#nowo-tech-widgets-supported)
- [Conditional fields (show one field or another)](#conditional-fields-show-one-field-or-another)
  - [1. Build-time (`if` on known options / data)](#1-build-time-if-on-known-options--data)
  - [2. Form events (`PRE_SET_DATA` / `PRE_SUBMIT`)](#2-form-events-pre_set_data--pre_submit)
  - [3. UI-only hide/show (Stimulus / CSS)](#3-ui-only-hideshow-stimulus--css)
  - [4. Live Components (optional)](#4-live-components-optional)
- [Kit strategy (FormKitTrait / FormKitAbstractType)](#kit-strategy-formkittrait--formkitabstracttype)
- [Wrapped strategy (third-party types)](#wrapped-strategy-third-party-types)
- [Translations](#translations)
  - [Overriding the bundle messages (`NowoFormKitBundle` domain)](#overriding-the-bundle-messages-nowoformkitbundle-domain)
- [Disabling convention for a key](#disabling-convention-for-a-key)
- [Custom static blocks in the form (HR, alert)](#custom-static-blocks-in-the-form-hr-alert)
- [Input group (icon at start or end)](#input-group-icon-at-start-or-end)
- [Help modal (optional)](#help-modal-optional)
- [Combobox (Stimulus peer, optional)](#combobox-stimulus-peer-optional)
- [Locale tabs (one tab per language)](#locale-tabs-one-tab-per-language)
  - [Migrating your locale UI](#migrating-your-locale-ui)
- [Overriding bundle templates](#overriding-bundle-templates)
- [Multi-step forms (array-based wizard)](#multi-step-forms-array-based-wizard)
  - [Steps definition](#steps-definition)
  - [Services](#services)
  - [Controller example](#controller-example)
- [Rendering forms in Twig (REQ-TWIG-005)](#rendering-forms-in-twig-req-twig-005)
- [Form renderer component (Twig)](#form-renderer-component-twig)
- [Layout examples (Bootstrap and Tailwind)](#layout-examples-bootstrap-and-tailwind)
  - [Horizontal / grid (Bootstrap)](#horizontal--grid-bootstrap)
  - [Floating labels (Bootstrap 5)](#floating-labels-bootstrap-5)
  - [Bootstrap 5 example](#bootstrap-5-example)
  - [Tailwind CSS example](#tailwind-css-example)
- [CSRF-only and GET filter forms](CSRF.md)

## Usage strategies

Form Kit exposes four **usage strategies**. Pick one entry point; all share the same merge pipeline (`FormOptionsMerger`) and convention keys (`{form}.{field}.label` / `.placeholder` / `.help`).

| Strategy | ID | Entry point | Type names | Typical when |
|----------|----|-------------|------------|--------------|
| **Options** | `options` | [`FormOptionsTrait`](#options-strategy-formoptionstrait) | Symfony FQCN (`TextType::class`) | Recommended default for app `FormType` classes |
| **Kit** | `kit` | [`FormKitTrait`](#kit-strategy-formkittrait--formkitabstracttype) / `FormKitAbstractType` | snake_case via [`FormTypeMap`](CONFIGURATION.md#optional-and-custom-types-type_map) (`'text'`) | Prefer string types / YAML-friendly `buildFormFromArray` |
| **Controller** | `controller` | [`FormKitControllerTrait`](#controller-strategy) | FQCN resolved from map in helpers (`addTextType`) | Forms built in a controller without a dedicated `FormType` |
| **Wrapped** | `wrapped` | [`AbstractFormKitWrappedType`](#wrapped-strategy-third-party-types) | Your type wrapping a third-party FQCN | Reuse UX/vendor widgets with Form Kit conventions |

**Cross-cutting techniques** (usable inside Options / Kit / Controller):

| Technique | ID | What it is |
|-----------|----|------------|
| **Bound builder** | `bound-builder` | `withBuilder($builder, …)` + `add*Field()` so you do not repeat `$builder` |
| **Array build** | `array-build` | `buildFormFromArray()` / `buildFieldsFromArray()` — declare fields as one array |
| **Named config** | `named-config` | `#[FormKitConfig('bootstrap')]` or `setFormKitConfigName('…')` |
| **Direct merge** | `direct-merge` | Call `FormOptionsMerger::resolve()` yourself (e.g. in events or ad-hoc builders) |
| **Field options for events** | `resolve-field-options` | `resolveFieldOptions()` then `$form->add()` in `PRE_SET_DATA` / `PRE_SUBMIT` |

In docs and issues you can say e.g. “use the **Options** strategy with **bound-builder**” or “demo page uses **Kit** + **named-config**”.

## FrankenPHP worker mode

This bundle is compatible with FrankenPHP **worker** when the Symfony kernel is **reused** between requests (`FRANKENPHP_RESET_KERNEL` unset or `0` — the Runtime default). Bundle services hold only compiled configuration; they do not need `kernel.reset`.

Rules for integrators (shared form types and controllers survive for the worker lifetime):

1. Call `setFormKitFormName()`, `setFormKitConfigName()`, and translations setters **only** from the constructor / DI (or unconditionally at the start of `buildForm()`). Prefer `#[FormKitConfig('…')]` for the profile.
2. Pass request-dependent names via the `$formName` / `$configName` arguments on `add*Type()` / field helpers, or via form options — never stash them on the trait after reading the current request.
3. Locale resolvers must read the current locale/request **at call time** (e.g. inject `RequestStack`), not capture a `Request` / user / tenant when the setter runs.
4. Create `MultiStepWizardSession` with `MultiStepWizardSessionFactory::create()` **inside the action**; do not keep the object on a shared service property.

Full audit: [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md). Demo runtime: [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).

## FormOptionsMerger service

The **FormOptionsMerger** resolves final options for each field with cascading merge. It uses the configured `profiles` and `default_profile`: the selected profile (or the one passed to `resolve()`) provides `translation_domain`, `defaults`, `field_types`, and optional `by_form`.

1. **Profile defaults:** Convention keys `form_snake.field_snake.label`, `.placeholder`, `.help`, plus `translation_domain`, `attr` and `row_attr` from the active profile.
2. **Field type defaults:** From the profile’s `field_types` (key = short name like `text` or FQCN).
3. **`by_form` defaults / fields:** Optional per-form overrides (see [Configuration](CONFIGURATION.md#per-form-defaults-by_form)).
4. **Field options:** What you pass to `addWithDefaults()` or `buildFormFromArray()`; last wins. Use `label: false`, `placeholder: false` or `help: false` to disable the convention for that key.

**Explicit options win over convention keys.** A `placeholder` passed in PHP (or set in `field_types` / `by_form` / profile `defaults`) always replaces the `{form}.{field}.placeholder` convention key. For **`ChoiceType`** (and `EntityType`, `EnumType`, `CountryType`… or any type whose parent is `ChoiceType`), `placeholder` keeps Symfony's meaning — the empty-option label — and is **not** moved to `attr.placeholder`; `placeholder: false` renders no empty option. Choice, checkbox, radio and hidden fields never receive the convention placeholder key. Details: [Configuration — Placeholder semantics](CONFIGURATION.md#placeholder-semantics).

```php
$this->addWithDefaults($builder, 'status', ChoiceType::class, [
    'choices'     => ['Open' => 'open', 'Closed' => 'closed'],
    'required'    => false,
    'placeholder' => 'ticket_filter.status.any', // empty option label (root option)
]);
$this->addWithDefaults($builder, 'priority', ChoiceType::class, [
    'choices'     => ['Low' => 1, 'High' => 2],
    'required'    => false,
    'placeholder' => false, // no empty option at all
]);
```

You can inject **FormOptionsMerger** and call `resolve($formName, $fieldName, $type, $options, $configName)` directly (**direct-merge** technique), e.g. when building a form in the controller without a FormType class.

## Controller strategy

**Strategy ID:** `controller` · **API:** `Nowo\FormKitBundle\Controller\FormKitControllerTrait`

If you build forms in controllers (without a Symfony `FormType`), use this trait.

It provides helpers like `addTextType()`, `addEmailType()`, `addChoiceType()`, choice presets (`addSelectType`, `addMultiSelectType`, `addChoiceRadiosType`, `addChoiceCheckboxesType`, `addMultiSelectSelectAllType`), `addAutocompleteFieldType`, `addCKEditorFieldType`, `addDropzoneFieldType`, `addCropperFieldType`, optional nowo-tech widgets (`addOtpFieldType`, `addPhoneFieldType`, `addPasswordToggleFieldType`, `addPasswordStrengthFieldType`, `addIconSelectorFieldType`, `addCkeditor5EditorFieldType`, `addTiptapEditorFieldType`, `addMilkdownEditorFieldType`, `addTagInputFieldType`, `addSlideToConfirmFieldType`, `addAltchaFieldType`, `addBarcodeFieldType`, `addDateRangeFieldType`; see [nowo-tech widgets supported](#nowo-tech-widgets-supported)), plus transformer presets like:
- `addSwitchType()` (model int/bool <-> ChoiceType switch)
- `addJsonType()` (model array <-> JSON textarea)
- `addBoolType()` (model 0/1 <-> CheckboxType)
- `addMoneyType()` (model cents <-> decimal string)
- `addCsvType()` (model array<string> <-> CSV textarea)
Those:
- resolve the Symfony FQCN for the field type via `FormTypeMap` (so you do not need to import `TextType`, `EmailType`, ...)
- merge options via `FormOptionsMerger` (YAML defaults + convention-based label/placeholder/help)
- support two ways to choose the `$formName` used for conventions:
  - fix it once in the **constructor** with `setFormKitFormName('controller_contact')` (required under FrankenPHP worker — do not call setters inside an action)
  - or pass `$formName` per call (last argument on `add*Type()` methods)

Example:

```php
use Nowo\FormKitBundle\Controller\FormKitControllerTrait;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Symfony\Component\Form\FormBuilderInterface;

final class MyController
{
    use FormKitControllerTrait;

    public function __construct(FormOptionsMerger $merger, FormTypeMap $typeMap)
    {
        $this->setFormOptionsMerger($merger);
        $this->setFormTypeMap($typeMap);
        $this->setFormKitFormName('controller_contact');
    }

    private function createForm(FormBuilderInterface $builder): void
    {
        $this->addTextType($builder, 'name');
        $this->addEmailType($builder, 'email');
        $this->addTextareaType($builder, 'message');
    }
}
```

## Options strategy (FormOptionsTrait)

**Strategy ID:** `options` · **API:** `Nowo\FormKitBundle\Form\FormOptionsTrait` · **Recommended default** for application form types.

1. **Register your form type as a service** and inject the merger:

```yaml
# config/services.yaml
App\Form\UserProfileType:
    tags: ['form.type']
    calls:
        - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
```

2. **In your form type**, use the trait and either the typed helpers (`addText()`, `addEmail()`, …), `addWithDefaults()`, **bound-builder** (`withBuilder` + `add*Field`), or **array-build** (`buildFormFromArray()`):

**Typed helpers (field name + options only, no type class) + bound-builder:**

```php
use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

#[FormKitConfig('bootstrap')] // optional: named config; or call setFormKitConfigName()
class UserProfileType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addTextField('full_name');
            $this->addEmailField('email_address');
            $this->addTextareaField('message');
            $this->addTextField('internal_note', ['label' => false]); // no label
        });
    }
}
```

**Named config** technique: put `#[FormKitConfig('bootstrap')]` on the form class (reads `nowo_form_kit.profiles.bootstrap`), or call `setFormKitConfigName('bootstrap')` in `buildForm()` / DI. An explicit `setFormKitConfigName()` call overrides the attribute.

You can still pass `$builder` explicitly with the older helpers (`addText($builder, …)`, `addEmail($builder, …)`, …). `boundBuilder()` returns the builder bound by `withBuilder()` when you need helpers that still require it (e.g. `addAutocompleteField`, `addCKEditorField`).

**Building the form from an array:** Define all fields in one array and call `buildFormFromArray($builder, $fields)` or, inside `withBuilder()`, `buildFieldsFromArray($fields)`. Each key is the field name; the value is either the type FQCN (e.g. `TextType::class`) or an array with a required `type` key and any other options. Options are still merged by convention and config.

```php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

$this->buildFormFromArray($builder, [
    'full_name' => TextType::class,
    'email_address' => EmailType::class,
    'topic' => ['type' => ChoiceType::class, 'choices' => ['Support' => 'support', 'Other' => 'other']],
]);
```

**Or** use `addWithDefaults($builder, $name, TextType::class, $options)` when you need a type not covered by the helpers or a custom type.

**Available typed helpers (core):** `addText` / `addTextField`, `addEmail` / `addEmailField`, `addTextarea` / `addTextareaField`, `addPassword` / `addPasswordField`, `addUrl` / `addUrlField`, `addInteger` / `addIntegerField`, `addNumber` / `addNumberField`, `addCheckbox` / `addCheckboxField`, `addChoice` / `addChoiceField`. Prefer `withBuilder($builder, …)` + `*Field` when adding several fields.

**Choice presets** (wrap `ChoiceType` with common `expanded` / `multiple` combinations): `addSelect` / `addSelectField`, `addMultiSelect` / `addMultiSelectField`, `addChoiceRadios` / `addChoiceRadiosField`, `addChoiceCheckboxes` / `addChoiceCheckboxesField`. Radios and checkbox groups clear the global `form-control` class on the widget root and disable `placeholder` by default so Bootstrap 5 `form-check` markup renders correctly. **`addMultiSelectSelectAll`** / **`addMultiSelectSelectAllField`** adds `select_all: true` for **nowo-tech/select-all-choice-bundle**; it throws `LogicException` if that bundle is not installed (use `addMultiSelect` instead, or install the package — see Composer **suggest** in the bundle’s `composer.json`).

**FQCN helpers:** `addAutocompleteField($builder, $name, $formTypeFqcn, $options)` for Symfony UX Autocomplete (or any custom form type class). **`addCKEditorField`** requires **friendsofsymfony/ckeditor-bundle** (CKEditor 4). **`addDropzone` / `addDropzoneField`** require **symfony/ux-dropzone**. **`addCropper` / `addCropperField`** require **symfony/ux-cropperjs** (pass Cropper options such as `public_url`). **Nowo-tech widgets** (optional Composer **suggest**, `LogicException` if missing): `addOtp`, `addPhone`, `addPasswordToggle` (not Symfony `addPassword()`), `addPasswordStrength`, `addIconSelector`, `addCkeditor5Editor` (not FOS `addCKEditorField()`), `addTiptapEditor`, `addMilkdownEditor` (Markdown), `addTagInput` (do not pass `placeholder => false`; `TagType` expects a string), `addSlideToConfirm` (defaults to `mapped: false`; set `mapped => true` to bind the value), `addAltcha` (ALTCHA, `mapped: false` by default), `addBarcode`, `addDateRange` (ui-kit-bundle ^2.15.0). All run through the same merge pipeline. Inside `withBuilder()`, use the `*Field` variants or pass `$this->boundBuilder()`. Generic bound helper: `addTypedField($name, $typeFqcn, $options)`. On **FormKitTrait**, the same helpers resolve snake_case entries in **FormTypeMap** (`otp`, `phone`, `password_toggle`, `password_strength`, `icon_selector`, `ckeditor5`, `tiptap`, `milkdown`, `tag`, `slide_to_confirm`, `altcha`, `barcode`, `date_range`, plus `dropzone` / `cropper`). Some widgets render Twig that translates in domain **`messages`** (e.g. PhoneInput country picker, SlideToConfirm gate labels); add those keys to your catalogue or set each field’s `translation_domain` to the bundle domain.

**Model transformers:** `addSwitchType` / `addSwitchField`, `addJsonType` / `addJsonField`, `addBoolType` / `addBoolField`, `addMoneyType` / `addMoneyField`, `addCsvType` / `addCsvField`.

**Layout:** `addFieldBreak` inserts a full-width break in grid layouts (optional).

The form block prefix (e.g. `user_profile` for `UserProfileType`) is used automatically. Field names are used as-is for the translation key segment (use snake_case for consistency: `full_name`, `email_address`).

Equivalent **controller** methods on `FormKitControllerTrait` use the `*Type` / `*FieldType` suffix (e.g. `addSelectType`, `addCKEditorFieldType`, `addDropzoneFieldType`, `addCropperFieldType`, `addOtpFieldType`, `addCkeditor5EditorFieldType`, `addMilkdownEditorFieldType`).

## nowo-tech widgets supported

| Map key | Composer package | Form type | `FormKitTrait` / `FormOptionsTrait` | `FormKitControllerTrait` | Notes |
|---------|------------------|-----------|-------------------------------------|--------------------------|-------|
| `otp` | nowo-tech/otp-input-bundle | `Nowo\OtpInputBundle\Form\OtpType` | `addOtp()` / `addOtpField()` | `addOtpFieldType()` | |
| `phone` | nowo-tech/phone-input-bundle | `Nowo\PhoneInputBundle\Form\Type\PhoneType` | `addPhone()` / `addPhoneField()` | `addPhoneFieldType()` | Country picker labels use domain `messages` |
| `password_toggle` | nowo-tech/password-toggle-bundle | `Nowo\PasswordToggleBundle\Form\Type\PasswordType` | `addPasswordToggle()` / `addPasswordToggleField()` | `addPasswordToggleFieldType()` | Distinct from Symfony `addPassword()` |
| `password_strength` | nowo-tech/password-strength-bundle | `Nowo\PasswordStrengthBundle\Form\PasswordStrengthType` | `addPasswordStrength()` / `addPasswordStrengthField()` | `addPasswordStrengthFieldType()` | |
| `icon_selector` | nowo-tech/icon-selector-bundle | `Nowo\IconSelectorBundle\Form\IconSelectorType` | `addIconSelector()` / `addIconSelectorField()` | `addIconSelectorFieldType()` | |
| `ckeditor5` | nowo-tech/ckeditor5-editor-bundle | `Nowo\Ckeditor5EditorBundle\Form\Ckeditor5EditorType` | `addCkeditor5Editor()` / `addCkeditor5EditorField()` | `addCkeditor5EditorFieldType()` | Distinct from FOS `addCKEditorField()` (CKEditor 4) |
| `tiptap` | nowo-tech/tiptap-editor-bundle | `Nowo\TiptapEditorBundle\Form\TiptapEditorType` | `addTiptapEditor()` / `addTiptapEditorField()` | `addTiptapEditorFieldType()` | |
| `milkdown` | nowo-tech/milkdown-editor-bundle | `Nowo\MilkdownEditorBundle\Form\MilkdownEditorType` | `addMilkdownEditor()` / `addMilkdownEditorField()` | `addMilkdownEditorFieldType()` | Markdown value; the resolved placeholder (convention key or explicit) is also passed as the root `placeholder` option the editor reads; options `profile`, `features`, `theme`, `min_height`, `placeholder`, `suggestions`, `upload_url`, `collaboration` (see the bundle docs) |
| `tag` | nowo-tech/tag-input-bundle | `Nowo\TagInputBundle\Form\TagType` | `addTagInput()` / `addTagInputField()` | `addTagInputFieldType()` | Do not pass `placeholder => false` |
| `slide_to_confirm` | nowo-tech/slide-to-confirm-bundle | `Nowo\SlideToConfirmBundle\Form\Type\SlideToConfirmType` | `addSlideToConfirm()` / `addSlideToConfirmField()` | `addSlideToConfirmFieldType()` | `mapped: false` by default |
| `altcha` | nowo-tech/altcha-type-bundle | `Nowo\AltchaTypeBundle\Form\Type\AltchaType` | `addAltcha()` / `addAltchaField()` | `addAltchaFieldType()` | ALTCHA proof-of-work; `mapped: false`, `label: false` by default; the helpers default `required` to `true` so a profile-wide `required: false` cannot drop the check (pass `required => false` to opt out); options `profile`, `floating`, `hide_logo`, `hide_footer`, `challenge_url` |
| `barcode` | nowo-tech/barcode-input-bundle | `Nowo\BarcodeInputBundle\Form\BarcodeType` | `addBarcode()` / `addBarcodeField()` | `addBarcodeFieldType()` | Text input with camera scanner; options `enable_scanner`, `formats`, `max_length`, `facing_mode`, `trim_whitespace` |
| `date_range` | nowo-tech/ui-kit-bundle (^2.15.0) | `Nowo\UiKitBundle\Form\DateRangeType` | `addDateRange()` / `addDateRangeField()` | `addDateRangeFieldType()` | From/to filter field (`['from' => ?string, 'to' => ?string]`); options `from_name`, `to_name`, `placeholder`, `picker` |

Each helper throws `LogicException` when its package is not installed; on `FormKitTrait` the map key can also be
remapped in `nowo_form_kit.type_map`. `FormTypeMap::NOWO_WIDGETS` is the central list of these widgets and a test
fails when one is missing from the map, the Composer `suggest`, the helpers or this table. The **Select all** choice
(`nowo-tech/select-all-choice-bundle`) is a `ChoiceType` extension, not a map key: use
`addMultiSelectSelectAll()` / `addMultiSelectSelectAllField()` / `addMultiSelectSelectAllType()`.

Editors (CKEditor 5, Tiptap, Milkdown) inside locale tabs or other panels mount when their panel shows; see
[Locale tabs](#locale-tabs-one-tab-per-language).

## Conditional fields (show one field or another)

Form Kit has no built-in `when` / `visible_if` option. Conditionals stay in Symfony (build-time `if`, `FormEvents`, or frontend). Use any strategy’s helpers: **bound-builder** + `add*Field()` when the shape is known at build time, or the **resolve-field-options** technique + `$form->add()` inside listeners.

### 1. Build-time (`if` on known options / data)

Use when the condition does **not** change within the same request (e.g. form option, role, feature flag):

```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $this->withBuilder($builder, function () use ($options): void {
        $this->addChoiceRadiosField('account_type', [
            'choices' => ['Individual' => 'individual', 'Company' => 'company'],
        ]);

        if (($options['account_mode'] ?? 'individual') === 'company') {
            $this->addTextField('company_name');
        } else {
            $this->addTextField('first_name');
            $this->addTextField('last_name');
        }
    });
}
```

### 2. Form events (`PRE_SET_DATA` / `PRE_SUBMIT`)

Use when the visible fields depend on a value in the form data (including the submitted payload). Listeners receive a `FormInterface`, so call `$form->add()` / `$form->remove()` with options from `resolveFieldOptions()` (same merge pipeline as `addTextField()`).

```php
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $row = ['row_attr' => ['class' => 'col-12 mb-3']];

    $this->withBuilder($builder, function () use ($row): void {
        $this->addChoiceRadiosField('account_type', array_merge($row, [
            'choices' => [
                'Individual' => 'individual',
                'Company'    => 'company',
            ],
        ]));
    });

    $adapt = function (FormInterface $form, ?string $accountType) use ($row): void {
        foreach (['company_name', 'first_name', 'last_name'] as $name) {
            if ($form->has($name)) {
                $form->remove($name);
            }
        }

        if ($accountType === 'company') {
            $form->add('company_name', TextType::class, $this->resolveFieldOptions('company_name', TextType::class, $row));
        } else {
            $form->add('first_name', TextType::class, $this->resolveFieldOptions('first_name', TextType::class, $row));
            $form->add('last_name', TextType::class, $this->resolveFieldOptions('last_name', TextType::class, $row));
        }
    };

    $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($adapt): void {
        $data = $event->getData();
        $type = is_object($data) ? ($data->account_type ?? null) : ($data['account_type'] ?? null);
        $adapt($event->getForm(), is_string($type) ? $type : 'individual');
    });

    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($adapt): void {
        $data = $event->getData();
        $type = is_array($data) ? ($data['account_type'] ?? null) : null;
        $adapt($event->getForm(), is_string($type) ? $type : 'individual');
    });
}
```

Demo: `/conditional-fields` — events form (`ConditionalFieldsDemoType`) and build-time form (`BuildTimeConditionalDemoType`) in **demo/symfony8**. Also `/kit-api-patterns` for `FormKitAbstractType` (snake_case) and named config (`#[FormKitConfig('bootstrap')]`), plus `/conditional-fields-live` for Live Components.

**Twig tip:** render dynamic children with `is defined` or rely on `form_rest()`:

```twig
{{ form_row(form.account_type) }}
{% if form.company_name is defined %}{{ form_row(form.company_name) }}{% endif %}
{% if form.first_name is defined %}{{ form_row(form.first_name) }}{% endif %}
{% if form.last_name is defined %}{{ form_row(form.last_name) }}{% endif %}
```

Changing the driving field in the browser does **not** rebuild the form until the next request (submit or Live Component refresh). Align validation (groups / constraints) with the fields that exist for each branch.

### 3. UI-only hide/show (Stimulus / CSS)

Add **all** fields with Form Kit helpers, then toggle visibility in the browser. Keep server-side validation consistent (optional fields, validation groups, or constraints that match the selected branch). Good for polish; not a substitute for `PRE_SUBMIT` when a hidden field must not be submitted or validated.

### 4. Live Components (optional)

For instant re-render without a full page submit, wrap the form in a Symfony UX Live Component (`ComponentWithFormTrait`) and pass the driving value as a form option (build-time `if`) or rebuild via Live props. Form Kit stays the options/convention layer; Live owns the refresh cycle.

Requires **symfony/ux-live-component**. Demo: `/{locale}/conditional-fields-live` in **demo/symfony8** (`ConditionalFieldsLive` component + `BuildTimeConditionalDemoType`).

## Kit strategy (FormKitTrait / FormKitAbstractType)

**Strategy ID:** `kit` · **API:** `FormKitTrait` / `FormKitAbstractType` + `FormTypeMap`

If you prefer **snake_case type names** instead of FQCNs, use this strategy. The bundle registers **FormTypeMap** with built-in types (`text`, `email`, `choice`, `date`, `money`, …) and optional types when the package is present (e.g. `dropzone`, `cropper`, `translations`). Extend via `nowo_form_kit.type_map` (see [Configuration](CONFIGURATION.md)).

- **FormKitTrait** provides `addField($builder, $name, $typeSnakeCase, $options)` and `buildFormFromArray($builder, $fields)` where each field’s type is a string (e.g. `'text'`, `'choice'`) instead of a class. It uses **FormOptionsMerger** for the option cascade and **FormTypeMap** for snake_case type resolution.
- **FormKitAbstractType** is a base class that uses FormKitTrait and injects **FormOptionsMerger** and **FormTypeMap** via the constructor, so it works with the same `profiles` / `default_profile` model as FormOptionsTrait.
- **Protected helpers on FormKitAbstractType (2.6.0):** `twigOwnedChromeOptions()` returns `label`, `help`, `placeholder` and `translation_domain` all `false` (use when Twig renders the visible copy). `addChoiceWithFormPlaceholder($name, $options)` (inside `withBuilder()`) adds a `ChoiceType` whose empty option defaults to the convention key `{block_prefix}.{field_snake}.placeholder` (plain choice fields get no convention key). Omit `placeholder` to use that key, pass a custom key, or `false` to render no empty option; `translation_domain` defaults to `form` when the empty option is kept.

Example with FormKitTrait (when both services are available):

```php
$this->buildFormFromArray($builder, [
    'full_name' => 'text',
    'topic' => ['type' => 'choice', 'choices' => ['Support' => 'support', 'Other' => 'other']],
]);
```

## Wrapped strategy (third-party types)

**Strategy ID:** `wrapped` · **API:** `Nowo\FormKitBundle\Form\AbstractFormKitWrappedType`

To use **your own form types** that wrap third-party types (e.g. UX Dropzone, Cropper) and still get the bundle’s convention (label, placeholder, help, `field_types.*`), extend **AbstractFormKitWrappedType**. Often combined with the **Options** strategy (`addWithDefaults`) or registered in `type_map` for the **Kit** strategy.

1. Implement **getInnerType()** and return the FQCN of the type you wrap.
2. Use your type with **FormOptionsTrait::addWithDefaults()** or **buildFormFromArray()** (pass your class as the type). The merger will apply convention using your type’s block prefix (derived from the class name, e.g. `DropzoneFieldType` → `dropzone_field`).
3. Optionally register your type in **nowo_form_kit.type_map** (snake_case name) and use it via **FormKitTrait::addField()** with that name.

Example:

```php
// App\Form\Type\DropzoneFieldType
use Nowo\FormKitBundle\Form\AbstractFormKitWrappedType;
use Symfony\UX\Dropzone\Form\DropzoneType;

final class DropzoneFieldType extends AbstractFormKitWrappedType
{
    protected function getInnerType(): string
    {
        return DropzoneType::class;
    }
}
```

```php
// In your form type (with FormOptionsTrait)
$this->addWithDefaults($builder, 'document', DropzoneFieldType::class, []);
```

Translations then use your form prefix + field name (e.g. `dropzone_demo.document.label`, `dropzone_demo.document.help`). The demo’s Dropzone page uses this pattern.

## Translations

Add entries in your translation domain (e.g. `messages` or a custom one set in config):

```yaml
# translations/messages.en.yaml
user_profile:
  full_name:
    label: Full name
    placeholder: Enter your full name
    help: As shown on your ID.
  email_address:
    label: Email address
    placeholder: you@example.com
    help: We will not share it.
```

### Overriding the bundle messages (`NowoFormKitBundle` domain)

The bundle ships its own UI strings (the [locale tabs](#locale-tabs-one-tab-per-language) labels) in the translation
domain **`NowoFormKitBundle`**, with catalogues for `en`, `es`, `fr`, `de`, `it`, `pt` and `nl` under
`src/Resources/translations/NowoFormKitBundle.<locale>.yaml`. Application translations take priority over bundle
translations, so the bundle catalogue only acts as a fallback for keys your app does not define.

To override (or add a locale):

1. Use the same domain as the bundle: `NowoFormKitBundle`.
2. Create `translations/NowoFormKitBundle.<locale>.yaml` (or `.xlf`) in your application.
3. Define only the keys you want to change; every missing key falls back to the bundle value **for the locales the bundle ships**. For a locale the bundle does not ship (e.g. `ja`), define every key in your file or keep a shipped locale in `framework.translator.fallbacks` (default: your `default_locale`, so keep it to one of the 7 above or add `en`), otherwise Symfony prints the key itself.

```yaml
# translations/NowoFormKitBundle.es.yaml (application)
locale_tabs.label: "Traducciones"
locale_tabs.required: "requerido"
```

| Key | Default (`en`) | Where it is used |
| --- | --- | --- |
| `locale_tabs.label` | `Languages` | Accessible name of the tab list when no `tabs_label` / field label is given |
| `locale_tabs.default` | `default language` | Screen-reader hint on the default locale tab |
| `locale_tabs.required` | `required` | Screen-reader hint on tabs of required locales |
| `locale_tabs.invalid` | `contains errors` | Screen-reader hint on tabs whose panel has validation errors |

Run `php bin/console cache:clear` after adding a new translation file, and `php bin/console debug:translation es --domain=NowoFormKitBundle`
to check which value wins. Bundle maintainers validate the catalogues with `make validate-translations` (YAML syntax and key parity across locales).

## Disabling convention for a key

Pass `false` in the options to omit the convention-based key:

```php
$this->addText($builder, 'internal_note', [
    'label' => false,       // no label
    'placeholder' => false, // no placeholder
    'help' => false,        // no help
]);
```

## Custom static blocks in the form (HR, alert)

When rendering forms with the form_renderer loop (or any `form_row` loop), you can insert **non-input** blocks such as a horizontal rule or a translatable alert. The bundle provides two form types for this:

- **StaticSeparatorType** – Renders an `<hr>` in the form flow. Add it like any other field; it is not mapped and has no label.
- **StaticAlertType** – Renders a Bootstrap-style alert with a translatable message. Options: `message` (required, translation key), `alert_type` (e.g. `info`, `warning`, `success`), `translation_domain`.

**1. Register the form theme** so Twig knows how to render these types. List **`@NowoFormKitBundle/form/static_blocks.html.twig` first** (lowest priority), then your CSS framework layout (e.g. Bootstrap 5). If static blocks is registered *after* Bootstrap 5, Symfony may inherit bare `form_div` radio/checkbox widgets from the theme chain and break expanded choices (`addChoiceRadios`, `addChoiceCheckboxes`, Select All Choice checkboxes).

```yaml
# config/packages/twig.yaml
twig:
  form_themes:
    - '@NowoFormKitBundle/form/static_blocks.html.twig'
    - 'bootstrap_5_layout.html.twig'
    # Other bundle themes (Select All Choice, CKEditor, …) after Bootstrap 5
```

This theme also appends `required_label_suffix` inside the label. It checks `required is defined` first: Symfony Submit/Button/Reset widgets call `form_label_content` without that variable, which would 500 under Twig `strict_variables`.

**2. Add the types to your form** (e.g. in `buildFormFromArray` or with `addWithDefaults`):

```php
use Nowo\FormKitBundle\Form\Type\StaticAlertType;
use Nowo\FormKitBundle\Form\Type\StaticSeparatorType;

$this->buildFormFromArray($builder, [
    'full_name' => TextType::class,
    'message' => TextareaType::class,
    '_notice' => [
        'type' => StaticAlertType::class,
        'message' => 'my_form.notice_message',
        'label' => false,
    ],
    '_sep' => ['type' => StaticSeparatorType::class, 'label' => false],
    'accept_terms' => CheckboxType::class,
]);
```

They will appear in order when you use the form_renderer or iterate with `{% for child in form %}{{ form_row(child) }}{% endfor %}`. Add the translation for `my_form.notice_message` in your domain.

## Input group (icon at start or end)

You can add a prefix or suffix to any field so it renders inside Bootstrap’s **input-group** (e.g. `@` for email, 🔒 for password). The bundle adds two options to all form types via **InputGroupExtension**:

- **input_group_prefix** – Rendered in a `<span class="input-group-text">` before the widget.
- **input_group_suffix** – Rendered in a `<span class="input-group-text">` after the widget.

Use the bundle’s form theme (`@NowoFormKitBundle/form/static_blocks.html.twig`); when either option is set, the row wraps the widget in an `input-group` div. You can pass plain text (e.g. `'@'`) or HTML (e.g. an icon `<i class="bi bi-envelope">`); the theme outputs it with `|raw`.

Example:

```php
$this->buildFormFromArray($builder, [
    'email_address' => [
        'type' => EmailType::class,
        'input_group_prefix' => '@',
    ],
    'password' => [
        'type' => PasswordType::class,
        'input_group_prefix' => '🔒',
    ],
    'website' => [
        'type' => UrlType::class,
        'input_group_suffix' => '🔗',
    ],
]);
```

## Help modal (optional)

**HelpModalExtension** adds an optional field option **`help_modal`**:

- `false` or omitted — no help trigger (default).
- `true` — use defaults from `profiles.<name>.help_modal` in `nowo_form_kit` (see [Configuration](CONFIGURATION.md)).
- `array` — merge with those defaults; keys include `id`, `framework` (`bootstrap5`, `bootstrap4`, `tailwind`, `foundation`), `icon_html`, optional `ux_icon` / `ux_icon_attributes` when **symfony/ux-icons** is installed, `title` (plain text), `title_html` (trusted HTML), `content` (plain text body, shown escaped), `content_html` (trusted HTML body; wins over `content`), `trigger_class`, `aria_label`. The `*_html` keys and `icon_html` are inserted as HTML: developer markup only, never visitor input.

The extension sets `label_attr['data-nowo-help-modal']` to a JSON payload. The bundled script **`help-modal.js`** (built from TypeScript) scans `label[data-nowo-help-modal]`, injects the icon beside the label, and opens a modal. **Bootstrap 5** uses `window.bootstrap.Modal` when available; **Bootstrap 4** uses jQuery `.modal()`; **Tailwind** and **Foundation** use inline shells and `[data-help-modal-close]` buttons.

Help modal roots are marked with `data-nowo-formkit-help-modal` and **always portaled to `document.body`**. A `MutationObserver` keeps watching the DOM so forms loaded later (Turbo / Live Component / AJAX) still get triggers, and if a help modal is re-injected inside a hidden/clipped container it is removed/replaced and moved to `body` again. Only modal roots created by the script are relocated — never generic `.modal` nodes nor page markup that copies the marker.

**Page nonce (required).** Each payload carries a per-request nonce; the script only honours labels inside a `<form>` whose payload nonce equals `<meta name="nowo-form-kit-help-modal-nonce">` in `<head>`. Print it in your layout with `{{ nowo_form_kit_help_modal_meta() }}` (or `{{ nowo_form_kit_help_modal_nonce() }}` for the bare value). Same-origin Turbo fetches send the page nonce back in the `X-Nowo-Form-Kit-Help-Modal-Nonce` header; the server reuses it only for same-origin **Turbo Frame** requests (`Turbo-Frame` header) and marks those responses `private` + `Vary`, so frame fragments stay bound. Your own `fetch()` calls that load forms must send both `Turbo-Frame: <any id>` and the nonce header. Do not shared-cache pages that mix `help_modal` fields with user content (see [Security](SECURITY.md#help-modal-html-frontend)). A Live Component re-render keeps the last bound payload of existing labels. See [Security — Help modal HTML](SECURITY.md#help-modal-html-frontend). Console diagnostics are off unless `window.NOWO_FORM_KIT_DEBUG = true` is set before the script loads.

**0. Optional: render modal shell templates** (lets you override markup per framework; the script clones from `<template id="nowo-formkit-help-modal-shell-*">` or falls back to built-in HTML):

```twig
{% include '@NowoFormKitBundle/help_modal/shells.html.twig' %}
```

Override under `templates/bundles/NowoFormKitBundle/help_modal/shell_*.html.twig` if needed.

**1. Install bundle public assets** (symlink or copy into `public/bundles/`):

```bash
php bin/console assets:install public --symlink
```

**2. Print the nonce in `<head>` and load the script after Bootstrap** (order matters: Bootstrap first, then this script):

```twig
{# in <head> #}
{{ nowo_form_kit_help_modal_meta() }}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" ...></script>
<script defer src="{{ asset('help-modal.js', 'nowo_form_kit') }}"></script>
<link rel="stylesheet" href="{{ asset('help-modal.css', 'nowo_form_kit') }}">
```

**3. Enable on a field** (example with explicit title and trusted HTML content):

```php
$this->addText($builder, 'full_name', [
    'help_modal' => [
        'title' => 'Full name',
        'content_html' => '<p>Use your legal name.</p>', // or 'content' => 'Use your legal name.' (plain text)
    ],
]);
```

Or rely on config defaults only: `'help_modal' => true`.

## Combobox (Stimulus peer, optional)

Since **2.12.0** the bundle ships an **opt-in** Stimulus controller for a searchable picker over a short, server-rendered list of options (WAI-ARIA 1.2 editable combobox with list autocomplete): `src/Resources/assets/stimulus-peers/combobox_controller.ts`, identifier **`combobox`**. It is not built into `public/` and not registered by the bundle; nothing changes until you import it. Your app needs `@hotwired/stimulus` (Symfony UX StimulusBundle, AssetMapper or Vite). Spec: [`specs/003-combobox-peer`](../specs/003-combobox-peer/spec.md).

**1. Register the peer** (keep the vendor path; adjust the relative path to your `assets/` layout):

```ts
// assets/controllers/combobox_controller.ts (host re-export; Stimulus Bridge / Vite)
export { default } from '../../vendor/nowo-tech/form-kit-bundle/src/Resources/assets/stimulus-peers/combobox_controller';
```

```ts
// or in your Stimulus bootstrap
import ComboboxController from '../vendor/nowo-tech/form-kit-bundle/src/Resources/assets/stimulus-peers/combobox_controller';
app.register('combobox', ComboboxController);
```

With AssetMapper, add the path to `importmap.php` or `assets/controllers.json` the same way as other vendor controllers (a TypeScript-capable pipeline such as `sensiolabs/typescript-bundle` is needed for `.ts` sources).

**2. Markup** — the form type only needs the data attributes; the options list is plain Twig:

```php
$builder
    ->add('canonical_uuid', HiddenType::class, [
        'attr' => ['data-combobox-target' => 'value'],
    ])
    ->add('query', SearchType::class, [
        'mapped' => false,
        'required' => false,
        'attr' => [
            'autocomplete' => 'off',
            'data-combobox-target' => 'query',
            'data-action' => 'input->combobox#filter focus->combobox#onQueryFocus keydown->combobox#onQueryKeydown',
        ],
    ]);
```

```twig
{{ form_start(form, {attr: {
    'data-controller': 'combobox',
    'data-action': 'submit->combobox#requireValue',
    'data-combobox-required-message-value': 'issue.duplicate.choose'|trans,
}}) }}
    {{ form_row(form.canonical_uuid) }}
    {{ form_label(form.query) }}
    {{ form_widget(form.query) }}
    <ul data-combobox-target="list" hidden>
        {% for item in candidates %}
            <li role="presentation">
                <button type="button" data-combobox-target="option" data-action="combobox#select"
                        data-value="{{ item.id }}"
                        data-label="{{ item.title|e('html_attr') }}"
                        data-search="{{ (item.title ~ ' ' ~ item.id)|e('html_attr') }}">{{ item.title }}</button>
            </li>
        {% endfor %}
        <li data-combobox-target="empty" hidden>{{ 'search.no_results'|trans }}</li>
    </ul>
{{ form_end(form) }}
```

| Kind | Name | Notes |
|------|------|-------|
| targets | `query`, `value`, `list`, `option`, `empty` (optional) | `option`: `data-value` (required to be selectable), `data-label` (default: trimmed text), `data-search` (default: text) |
| values | `open` (`false`), `requiredMessage` (`''`), `selectedClass` (`is-selected`), `activeClass` (`is-active`) | `requiredMessage` empty → query `data-required-message` → `"Please select an option."` |
| actions | `filter`, `onQueryFocus`, `onQueryKeydown`, `select`, `open`, `close`, `toggle`, `requireValue` | `requireValue` on the form `submit` blocks it while `value` is empty |
| events | `combobox:change` (`detail: {value, label}`) | The `value` input also gets a bubbling `change` |

**Accessibility / keyboard.** On connect the peer fills only missing attributes: `role="combobox"`, `aria-autocomplete="list"`, `aria-controls`, `aria-expanded`, `autocomplete="off"` on the query; `role="listbox"` and an id on the list; `role="option"`, ids and `aria-selected` on options. `ArrowDown` / `ArrowUp` open the list and move the active option (`aria-activedescendant`, class `activeClass`, wraps around); `Alt+ArrowDown` only opens; `Enter` picks the active option (or the first match when nothing is selected); `Escape` / `Tab` / a click outside close the list. Style it with your own CSS (`[data-combobox-target="option"].is-active`, `.is-selected`).

## Locale tabs (one tab per language)

Per-locale fields (one value per language: title, slug, SEO text, a label map) render as **one tab per locale**. Since
**2.13** the tabs render through UiKit's `locale_tabs` partial when **nowo-tech/ui-kit-bundle ^2.5** is installed
(suggested, not required): WAI-ARIA tabs with roving `tabindex` and arrows / Home / End (mirrored in RTL), every
panel visible as a stacked `<fieldset>` until JavaScript upgrades it, the browser opening the hidden panel of an
invalid control, the `nowo-ui:tabs:change` event for editors, Live Component safe, no inline JS / CSS. Without UiKit
(or with `ui_kit: false` / `nowo_form_kit.locale_tabs.renderer: native`) the neutral FormKit markup is used.

Both renderers: the default locale listed first and marked `★`, `required_locales` marked `*`, invalid tabs marked
`!` with "contains errors" in the tab's accessible name and opened first, the request locale opened otherwise,
`aria-invalid="true"` on every invalid widget, a translated tab list label (`locale_tabs.label`, domain
`NowoFormKitBundle`), `lang` / `dir` on each panel.

The theme `@NowoFormKitBundle/form/locale_tabs_theme.html.twig` is registered in `twig.form_themes` automatically
(`nowo_form_kit.locale_tabs.form_theme: false` to opt out). Three shapes:

**(a) A list of translation entities** (a `CollectionType` whose entries have a `locale` child): the locale comes from
the entry's `locale` value (or its data), that child becomes a hidden input, the other children of the entry fill
the panel.

```php
$builder->add('translations', CollectionType::class, [
    'entry_type'    => ArticleTranslationType::class, // locale (hidden or text), title, body…
    'entry_options' => ['error_bubbling' => false],   // entry-level errors inside their panel
    'locale_tabs'   => ['default_locale' => 'es', 'required_locales' => ['es']],
]);
```

Entity-level errors (`#[Assert\Valid]` on the collection, constraints of the translation entity) are mapped back to the
form through the collection key, so the collection must be **keyed by locale** for them to land inside the right
tab. With an unmapped collection (`'mapped' => false`, filled from the entity in the controller or a listener) or a
Doctrine collection keyed by position, the errors show above the tabs instead. Index the association by locale:

```php
#[ORM\OneToMany(targetEntity: ArticleTranslation::class, mappedBy: 'article', cascade: ['persist'], indexBy: 'locale')]
#[Assert\Valid]
private Collection $translations;

public function addTranslation(ArticleTranslation $translation): void
{
    $this->translations->set($translation->getLocale(), $translation); // key = locale, also for new entities
    $translation->setArticle($this);
}
```

```php
// mapped: false — pass the same locale-keyed collection as data:
$builder->add('translations', CollectionType::class, [
    'mapped'        => false,
    'data'          => $article->getTranslations(), // keys: es, en…
    'entry_type'    => ArticleTranslationType::class,
    'entry_options' => ['error_bubbling' => false],
    'locale_tabs'   => ['default_locale' => 'es'],
]);
```

Render-time options are merged over the type option (2.13.1; the row variables win):
`{{ form_row(form.translations, {locale_tabs: {active_locale: 'en'}}) }}`.

**(b) `field_<locale>` groups** (flat, often unmapped fields such as `name_es`, `description_es`): include the
partial with `fields` (and `pattern`, default `{field}_{locale}`); only those children are rendered, `form_rest()`
renders the others. `locales` defaults to `nowo_form_kit.locale_tabs.locales`.

```twig
{{ form_start(form) }}
    {{ form_row(form.code) }}
    {% include '@NowoFormKitBundle/form/_locale_tabs.html.twig' with {
        field: form, fields: ['name', 'description'], required_locales: ['es'], tabs_id: 'permission-i18n',
    } only %}
{{ form_end(form) }}
```

The same works on a compound child (`'locale_tabs' => ['fields' => ['name', 'description']]` on an `inherit_data`
sub-form).

**(c) `TranslatableMapType`** for a JSON `{locale: text}` map (Doctrine `json` column): one child per locale, locales
from `nowo_form_kit.locale_tabs.locales` (default `framework.enabled_locales`, else the default locale), tabs on by
default.

```php
$builder->add('labels', TranslatableMapType::class, [
    'required_locales' => ['es'],                 // required + NotBlank
    'entry_type'       => TextareaType::class,    // default TextType
    'entry_options'    => ['attr' => ['rows' => 2]],
    // 'locales' => ['es', 'en'], 'default_locale' => 'es', 'remove_empty' => true, 'input' => 'json'
]);
```

A `default_locale` that is not among `locales` falls back to the configured default locale when listed, else the
first locale (2.13.1), so the default tab never goes missing. Keys of the map that are not among the locales are kept on submit; blank values are dropped (`remove_empty`).
`input: 'json'` reads / writes a JSON string instead of an array.

**Children keyed by locale** (2.7 shape, e.g. `slugs[es]`, `slugs[en]`) keep working:

```php
$builder->add('slugs', SlugsPerLocaleType::class, [
    'label'       => 'Slug',
    'locale_tabs' => ['locales' => ['es', 'en', 'fr'], 'default_locale' => 'es', 'label' => 'Slug (%locale%)'],
]);
```

Options (option array or include): `mode` (`children` | `entries` | `fields`, detected), `fields`, `pattern`,
`locale_child` (default `locale`), `locales`, `default_locale`, `required_locales`, `active_locale`,
`activate_invalid` (default `true`), `default_child`, `tabs_id`, `testid`, `title`, `help`, `tabs_label`, `label` /
`help_default` / `help_other` (children mode, `%locale%` placeholder), `locale_labels`, `mark_default`,
`default_marker` / `required_marker` / `invalid_marker` (`★` / `*` / `!`), `row_options`, `stimulus_controller`
(default `tabs`, `false` to omit), `ui_kit` (`false` = neutral markup), UiKit renderer: `progressive`, `legend`,
`bundle`; neutral renderer: `kit_attributes` and the CSS hooks `wrapper_class`, `heading_class`, `help_class`,
`tabs_class`, `nav_class`, `tab_class`, `panel_class`, `error_marker`. The full list is in the partial header.

Configuration (`nowo_form_kit.locale_tabs`): `locales` (default `[]` = `framework.enabled_locales`), `default_locale`
(default `null` = `kernel.default_locale` when listed), `renderer` (`auto` | `ui_kit` | `native`), `form_theme`
(default `true`). See [CONFIGURATION](CONFIGURATION.md).

Neutral renderer styling: classes `nowo-form-kit-locale-tabs__*`, optional stylesheet
`{{ asset('locale-tabs.css', 'nowo_form_kit') }}`; switching needs `nowo-ui-tabs.js` or a Stimulus `tabs`
controller. UiKit renderer: UiKit's `nowo-ui.css` and `nowo-ui-tabs.js` (the UiKit `scripts` partial loads it), theme
slots `locale_tabs_nav` (UiKit 2.5.3+), `tab`, `tab_active`, `tab_panel`; the help gets the `muted` slot. The heading
(title size, muted help, label gap from `--nowo-ui-form-label-gap`) is styled by `locale-tabs.css` with both
renderers; tune it with `--nfk-locale-tabs-title-size`, `--nfk-locale-tabs-help-size`, `--nfk-locale-tabs-label-gap`.

Editors in panels (CKEditor, Tiptap, Milkdown) mount when their panel shows:

```js
document.addEventListener('nowo-ui:tabs:change', (event) => {
  event.detail.panel?.querySelectorAll('[data-editor]:not([data-mounted])').forEach(mountEditor);
});
```

Milkdown mounts every widget on load and when one is added to the page; with `data-nowo-milkdown-manual` on
`<html>`, call `window.NowoMilkdownEditor.mountAll(event.detail.panel)` in the same listener.

### Migrating your locale UI

Replace per-bundle locale tab markup with the shape that matches your data.

**List of translation entities** (BlogKit articles, ContactForm, ApiStudio, CookieConsent). Before — an own
`_locale_tabs.html.twig` / Bootstrap tabs looping over `form.translations`:

```twig
<ul class="nav nav-tabs">{% for t in form.translations %}<li><button data-bs-toggle="tab" data-bs-target="#t{{ loop.index }}">{{ t.vars.value.locale|upper }}</button></li>{% endfor %}</ul>
<div class="tab-content">{% for t in form.translations %}<div class="tab-pane" id="t{{ loop.index }}">{{ form_widget(t) }}</div>{% endfor %}</div>
```

After — the option on the collection, the template renders the row:

```php
$builder->add('translations', CollectionType::class, ['entry_type' => ArticleTranslationType::class, 'entry_options' => ['error_bubbling' => false], 'locale_tabs' => ['default_locale' => $defaultLocale]]);
```
```twig
{{ form_row(form.translations) }}
```

**`field_<locale>` groups** (SeoKit settings, Beacon permissions, DashboardMenu `label_<locale>`). Before — a fieldset or
row per locale:

```twig
{% for locale in locales %}<fieldset><legend>{{ locale|upper }}</legend>{{ form_row(form['title_' ~ locale]) }}{{ form_row(form['description_' ~ locale]) }}</fieldset>{% endfor %}
```

After:

```twig
{% include '@NowoFormKitBundle/form/_locale_tabs.html.twig' with {field: form, fields: ['title', 'description'], locales: locales} only %}
```

In a Live Component the fields keep their `data-model` attributes; the active tab survives re-renders.

**JSON map** (BreadcrumbKit labels, DashboardMenu item labels). Before — a raw JSON textarea or unmapped
`label_<locale>` fields merged by hand in a listener:

```php
$builder->add('labels', TextareaType::class); // + JsonModelTransformer
```

After:

```php
$builder->add('labels', TranslatableMapType::class, ['required_locales' => [$defaultLocale]]);
```

Unknown locales already in the JSON are kept, so a disabled locale is not lost.

**Editor remount**: replace a `locale-tabs:changed` (or Bootstrap `shown.bs.tab`) listener with
`nowo-ui:tabs:change` (`event.detail.panel`).

## Overriding bundle templates

The bundle provides Twig templates under `src/Resources/views/`:

- `components/form_renderer.html.twig` — helper include for rendering a form with buttons.
- `form/static_blocks.html.twig` — form theme used by `StaticSeparatorType` and `StaticAlertType` (and helpers like input-group prefix/suffix). `form_label_content` appends `required_label_suffix` only when `required` is defined and true, so Submit/Button/Reset widgets (which call this block without `required`) work with Twig `strict_variables`.
- `help_modal/shells.html.twig` — includes `<template>` fragments for help-modal shells (Bootstrap 4/5, Tailwind, Foundation); optional if you rely on the script’s built-in HTML fallbacks.

You can override any Twig template provided by the bundle by placing a file with the **same path** inside your project’s `templates/bundles/` directory. Symfony will use your template instead of the bundle’s.

**Important:** This bundle registers the Twig namespace **`NowoFormKitBundle`** via `TwigPathsPass`. Put overrides under `templates/bundles/NowoFormKitBundle/` so your application templates take precedence over the bundle defaults.

| Bundle path (relative to `Resources/views/`) | Override in your project |
|---------------------------------------------|--------------------------|
| `components/form_renderer.html.twig` | `templates/bundles/NowoFormKitBundle/components/form_renderer.html.twig` |
| `form/static_blocks.html.twig` | `templates/bundles/NowoFormKitBundle/form/static_blocks.html.twig` |
| `help_modal/shells.html.twig` | `templates/bundles/NowoFormKitBundle/help_modal/shells.html.twig` |
| `help_modal/shell_bootstrap5.html.twig` (and `shell_bootstrap4`, `shell_tailwind`, `shell_foundation`) | Same path under `templates/bundles/NowoFormKitBundle/help_modal/` |

After adding or changing overrides, clear the Twig cache if needed: `php bin/console cache:clear`.

## Multi-step forms (array-based wizard)

You can define a multi-step wizard as an array and use **MultiStepFormBuilder** plus **MultiStepWizardSessionFactory** to build the form for the current step with the same convention-based options (label, placeholder, help). Each step’s form name for conventions is `{wizardName}_{stepKey}` (e.g. `demo_wizard_contact`, `demo_wizard_address`).

### Steps definition

Define steps as an associative array: step key → `label` and `fields`. The `fields` array uses the same format as **buildFormFromArray** (field name => type FQCN or `['type' => ..., ...options]`). Step order is the order of array keys.

```php
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

$steps = [
    'contact' => [
        'label' => 'Contact',
        'fields' => [
            'fullName' => TextType::class,
            'email' => EmailType::class,
        ],
    ],
    'address' => [
        'label' => 'Address',
        'fields' => [
            'street' => TextType::class,
            'number' => TextType::class,
            'postalCode' => TextType::class,
            'city' => TextType::class,
            'province' => TextType::class,
        ],
    ],
    'confirm' => [
        'label' => 'Confirm',
        'fields' => [], // optional summary step
    ],
];
```

### Services

- **MultiStepFormBuilder::createStepForm(** `string $wizardName`, `string $stepKey`, `array $fieldsDefinition`, `array $data = []`, `?string $configName = null` **): FormInterface**  
  Builds a form containing only the fields for that step, with options merged via FormOptionsMerger (convention keys `{wizardName}_{stepKey}.{field_snake}.label`, etc.).

- **MultiStepWizardSessionFactory::create(** `array $steps`, `string $wizardName` **): MultiStepWizardSession**  
  Returns a session-backed wizard that stores current step index and collected data per step. Call it **inside the controller action** (FrankenPHP worker-safe: session is resolved from `RequestStack` on each access). Do not store the returned object on a shared service.

### Controller example

```php
$wizard = $this->wizardFactory->create($steps, 'my_wizard');

if ($wizard->isComplete()) {
    return $this->render('wizard/summary.html.twig', ['wizard' => $wizard]);
}

$stepKey = $wizard->getCurrentStepKey();
$form = $this->multiStepFormBuilder->createStepForm(
    'my_wizard',
    $stepKey,
    $wizard->getStepFields($stepKey),
    $wizard->getCollectedData()[$stepKey] ?? []
);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $wizard->setStepData($stepKey, $form->getData());
    $wizard->advance();
    return $this->redirectToRoute('app_wizard');
}

return $this->render('wizard/step.html.twig', ['form' => $form, 'wizard' => $wizard]);
```

Translation keys for each step follow the same pattern: e.g. `demo_wizard_contact.full_name.label`, `demo_wizard_contact.email.placeholder`, `demo_wizard_address.street.help`.

## Rendering forms in Twig (REQ-TWIG-005)

Every form in host apps, kit views and the demo is a PHP Form Type (prefer `FormKitAbstractType`) created in the controller and rendered only with the Twig form helpers. Render the body with the rendered-aware child loop (REQ-TWIG-003); custom markup for a few children first is fine, then the loop renders the rest (CSRF token included):

```twig
{{ form_start(form) }}
    {% for child in form %}
        {% if not child.rendered %}
            {{ form_row(child) }}
        {% endif %}
    {% endfor %}
    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

CSRF-only actions and GET filters use a Form Type too (see [CSRF-only and GET filter forms](CSRF.md)). Hosts and demos must not reintroduce raw `<form>` / `<input>` / `<textarea>` / `<select>` markup; `make check-no-raw-html-form` (part of `make release-check`) fails on them. Only form-theme files that implement Symfony widget blocks are allowlisted in `.scripts/check-no-raw-html-form.sh` (`form/_locale_tabs.html.twig` and the demo `nowo_special_fields_form_theme.html.twig`).

## Form renderer component (Twig)

The bundle provides a reusable Twig component that outputs `form_start`, all unrendered fields (via `form_rest`), an optional **buttons block**, and `form_end`. You control how many and which submit buttons (or links) appear by passing HTML into `form_buttons`. No single-submit limitation.

**Template:** `@NowoFormKitBundle/components/form_renderer.html.twig`

**Variables:**

| Variable             | Required | Description |
|----------------------|----------|-------------|
| `form`               | yes      | The form view. |
| `form_start_options` | no       | Options for `form_start()` (default: `{}`). |
| `form_button_names`  | no       | Array of form child names (e.g. `['save', 'cancel']`) to render in the buttons area. Use when buttons are form types (SubmitType/ButtonType). |
| `form_buttons`       | no       | HTML for one or more submit/buttons or links. Can be combined with `form_button_names`. |

**Submit as form type (recommended when you need to detect which button was clicked):**

```twig
{# In PHP: $builder->add('save', SubmitType::class); $builder->add('cancel', SubmitType::class); #}
{{ include('@NowoFormKitBundle/components/form_renderer.html.twig', { form: form, form_button_names: ['save', 'cancel'] }) }}
```

**Single submit (HTML):**

```twig
{% set form_buttons %}
  <button type="submit" class="btn btn-primary">{{ 'Submit'|trans }}</button>
{% endset %}
{{ include('@NowoFormKitBundle/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

**Multiple submits (HTML):**

```twig
{% set form_buttons %}
  <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
  <button type="submit" name="action" value="save_and_new" class="btn btn-outline-secondary">Save and new</button>
  <a href="{{ path('app_list') }}" class="btn btn-link">Cancel</a>
{% endset %}
{{ include('@NowoFormKitBundle/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

**Form-type buttons plus extra HTML (e.g. cancel link):**

```twig
{% set form_buttons %}<a href="{{ path('app_list') }}" class="btn btn-link">Cancel</a>{% endset %}
{{ include('@NowoFormKitBundle/components/form_renderer.html.twig', { form: form, form_button_names: ['save', 'save_and_new'], form_buttons: form_buttons }) }}
```

The buttons are wrapped in a `<div class="form-kit-buttons">` for styling. When you use `form_button_names`, the component renders the rest of the form first, then those children in the buttons div; otherwise it uses `form_rest()` for good performance and correct CSRF handling.

## Layout examples (Bootstrap and Tailwind)

The bundle does not enforce a CSS framework, but `defaults.attr`, `defaults.row_attr`, `field_types`, and `by_form` make it easy to standardize markup per project, theme, or individual form.

### Horizontal / grid (Bootstrap)

Use `row_attr` with column classes on fields (or `by_form` defaults), and wrap the form in a Bootstrap `row`:

```yaml
nowo_form_kit:
    profiles:
        default:
            alias: default
            translation_domain: messages
            defaults:
                attr: { class: 'form-control' }
                row_attr: { class: 'col-md-6 mb-3' }
            by_form:
                search:
                    defaults:
                        row_attr: { class: 'col-auto mb-0' }
            field_types:
                checkbox:
                    attr: { class: 'form-check-input' }
                    row_attr: { class: 'col-12 form-check mb-3' }
```

```twig
{{ form_start(form, { attr: { class: 'row g-3' } }) }}
  {{ form_row(form.full_name) }}
  {{ form_row(form.email) }}
{{ form_end(form) }}
```

### Floating labels (Bootstrap 5)

Keep Form Kit conventions for labels; in Twig use Bootstrap’s floating markup (or a custom form theme). Form Kit still supplies the label translation key:

```twig
<div class="form-floating mb-3">
  {{ form_widget(form.email) }}
  {{ form_label(form.email) }}
</div>
```

### Bootstrap 5 example

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    default_profile: bootstrap
    profiles:
        bootstrap:
            alias: bootstrap
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control'
                row_attr:
                    class: 'mb-3'
            field_types:
                checkbox:
                    attr:
                        class: 'form-check-input'
                    row_attr:
                        class: 'form-check mb-3'
                choice:
                    attr:
                        class: 'form-select'
```

Typical Twig rendering (using the bundle form renderer component):

```twig
{% set form_buttons %}
  <button type="submit" class="btn btn-primary">Save</button>
{% endset %}
{{ include('@NowoFormKitBundle/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

### Tailwind CSS example

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    default_profile: tailwind
    profiles:
        tailwind:
            alias: tailwind
            translation_domain: messages
            defaults:
                attr:
                    class: 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
                row_attr:
                    class: 'mb-4'
            field_types:
                textarea:
                    attr:
                        class: 'block w-full min-h-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
                checkbox:
                    attr:
                        class: 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500'
                    row_attr:
                        class: 'flex items-center gap-2 mb-4'
```

Recommended Tailwind submit button:

```twig
<button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
  Save
</button>
```
