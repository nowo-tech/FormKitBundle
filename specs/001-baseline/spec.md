# Feature Specification: FormKitBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Status**: Active  

**Package**: `nowo-tech/form-kit-bundle`  
**Configuration root**: `nowo_form_kit`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Symfony bundle to **reduce repetitive form field options**: convention-based translation keys (`form_snake.field_snake.label|placeholder|help`), named YAML configs with cascading merge (global → field type → form → field), Phase-2 / bound-builder field helpers (`withBuilder` + `add*Field`), `resolveFieldOptions` for FormEvents-driven conditionals, CSS-framework-aware class utilities, optional help-modal UX, static form blocks, model transformers, and multi-step wizard helpers.

---

## User Scenarios

### US-01 — Convention-based labels (P1)

**Given** a form using `FormOptionsTrait` with `FormOptionsMerger`, **When** `addText($builder, 'full_name')` runs without explicit label, **Then** translation keys resolve to `{form_snake}.full_name.label` (and `.placeholder`, `.help` when enabled).

### US-02 — Named configs & cascading merge (P1)

**Given** multiple entries under `nowo_form_kit.profiles`, **When** a form calls `setFormKitConfigName('bootstrap')`, **Then** `FormOptionsMerger` merges config defaults → field-type options → `by_form` defaults/fields → field options with later keys winning.

### US-02b — Per-form defaults & constraint message keys (P2)

**Given** `by_form.user_profile` and optional `constraint_message_convention: true`, **When** resolving a field on form `user_profile`, **Then** form-scoped attr/row_attr/field overrides apply, and constraints without an explicit message receive `{form}.{field}.constraints.{Name}` keys.

### US-07 — FormKitConfig attribute (P3)

**Given** a form type annotated with `#[FormKitConfig('bootstrap')]`, **When** fields are added via FormOptionsTrait / FormKitTrait without calling `setFormKitConfigName()`, **Then** options resolve using the `bootstrap` config. An explicit `setFormKitConfigName()` call overrides the attribute.

### US-03 — Phase-2 field helpers (P1)

**Given** `FormKitTrait` or `FormOptionsTrait`, **When** integrator calls `addEmail()`, `addChoice()`, `addSwitchType()`, etc., **Then** mapped Symfony types and transformers apply with merged options and constraint definitions from config.

### US-03d — Optional nowo-tech widgets (P2)

**Given** Composer **suggest** packages such as `nowo-tech/otp-input-bundle` (and peer nowo-tech form widgets), **When** the integrator calls `addOtp()` / `addOtpField()` / `addOtpFieldType()` (and peers: phone, password toggle/strength, icon selector, CKEditor 5, Tiptap, tag input, slide-to-confirm), **Then** Form Kit adds the corresponding FormType through the same merge pipeline. **And** if the package is not installed, the helper throws `LogicException`. **And** `FormTypeMap` registers snake_case names (`otp`, `phone`, `password_toggle`, `password_strength`, `icon_selector`, `ckeditor5`, `tiptap`, `tag`, `slide_to_confirm`) only when those classes exist. **And** Symfony `addPassword()` / FOS `addCKEditorField()` stay distinct from `addPasswordToggle()` / `addCkeditor5Editor()`. **And** **demo/symfony8** `/nowo-special-fields` uses those helpers, including TagInput and SlideToConfirm. **And** the demo loads widget assets after the Stimulus importmap (`page_scripts`), recompiles Asset Mapper output on container start, and supplies widget translation keys in domain `messages` when bundles fall back to that domain.

### US-03b — Bound-builder helpers (P1)

**Given** a form type using `FormOptionsTrait` or `FormKitTrait`, **When** `buildForm` calls `withBuilder($builder, fn () => …)` and inside it `addTextField('full_name')` (and peer `add*Field` helpers), **Then** fields are added on the bound builder with the same merge pipeline as `addText($builder, …)` and no per-call `$builder` argument is required. **And** `boundBuilder()` returns that builder for helpers that still need an explicit builder (e.g. `addAutocompleteField`). **And** calling `add*Field` / `boundBuilder()` outside `withBuilder` throws `LogicException`.

### US-03c — Options for FormEvents (P1)

**Given** a form using `FormOptionsTrait`, **When** a `PRE_SET_DATA` / `PRE_SUBMIT` listener calls `resolveFieldOptions($name, $type, $options)` and then `$form->add(...)`, **Then** the options match the convention/config merge used by `addWithDefaults` / `add*Field` (so conditional fields stay on the same pipeline).

### US-04 — Help modal & CSS frameworks (P2)

**Given** `help_modal` enabled in config and field option `help_modal` set, **When** the form renders, **Then** `HelpModalExtension` + `help-modal.js` show framework-specific shells (Bootstrap 4/5, Tailwind, Foundation) using `CssClassUtilities` for the configured `css_framework`.

### US-05 — Multi-step wizard (P2)

**Given** a wizard built with `MultiStepFormBuilder`, **When** user advances steps, **Then** `MultiStepWizardSession` persists partial data between requests via the session factory.

### US-06 — Conditional fields via Symfony patterns (P2)

**Given** an integrator needs to show company vs person fields, **When** they follow documented patterns (build-time `if`, `FormEvents` + `resolveFieldOptions`, UI hide, or Live Component), **Then** Form Kit remains the options/convention layer and does not require a dedicated `when` / `visible_if` field option. **And** **demo/symfony8** exposes `/conditional-fields` with both `ConditionalFieldsDemoType` (events) and `BuildTimeConditionalDemoType` (build-time `if`), `/conditional-fields-live` (Live Component), plus `/kit-api-patterns` for snake_case `FormKitAbstractType` and named config (`#[FormKitConfig]`).

---

## Requirements

### Bundle & config

- **FR-BUNDLE-001**: `NowoFormKitBundle` alias `nowo_form_kit` and register `TwigPathsPass` for the `NowoFormKitBundle` Twig namespace.
- **FR-CFG-001**: `Configuration` — `type_map`, `default_profile`, `css_framework`, named `profiles` (alias, translation_domain, required_label_suffix, help_modal, defaults, field_types).
- **FR-CFG-002**: `FormKitExtension` loads services and parameters; legacy root keys normalize to `default` config.

### DI

- **FR-DI-001**: `Resources/config/services.yaml` wires merger, CSS utilities, extensions, and type map.
- **FR-DI-002**: `FormOptionsMergerInjectorCompilerPass` injects merger into tagged form types.

### CSS framework

- **FR-CSS-001**: `CssFramework` + `CssClassUtilities` implementations (Bootstrap, Tailwind, Foundation, null) for column/class merge.

### Controller integration

- **FR-CTRL-001**: `FormKitControllerTrait` mirrors trait helpers for controller-built forms.

### Forms — core

- **FR-FORM-001**: `FormKitAbstractType`, traits, and abstract types for snake_case and wrapped types.
- **FR-FORM-002**: `FormOptionsMerger`, `FormKitOptionMerger` — cascading option resolution (including `by_form` and optional constraint message convention).
- **FR-FORM-003**: `FormFieldOptionsHelper` — convention keys and disable flags.
- **FR-FORM-004**: `FormTypeMap` — built-in + configurable type name → FQCN map.
- **FR-FORM-005**: Form extensions — input group, required suffix, help modal.
- **FR-FORM-006**: Model transformers — switch, JSON, bool, money, CSV.
- **FR-FORM-007**: `ConstraintDefinitionFactory` — constraints from YAML config.
- **FR-FORM-008**: Static alert/HTML/separator types; optional A2lix `TranslationsFormsType`.
- **FR-FORM-009**: Bound-builder API on `FormOptionsTrait` / `FormKitTrait`: `withBuilder()`, `boundBuilder()`, `add*Field()` / `buildFieldsFromArray()` / `addTypedField()` (and FormKit `addNamedField()`), preserving BC for existing `addText($builder, …)` helpers.
- **FR-FORM-010**: `FormOptionsTrait::resolveFieldOptions()` exposes the same merge as `addWithDefaults` for use with `FormInterface::add` in form event listeners (conditional fields).
- **FR-FORM-011**: `#[FormKitConfig('name')]` on a form type selects `nowo_form_kit.profiles.<name>` unless `setFormKitConfigName()` was called.
- **FR-FORM-012**: Optional nowo-tech widget helpers (`addOtp`, `addPhone`, `addPasswordToggle`, `addPasswordStrength`, `addIconSelector`, `addCkeditor5Editor`, `addTiptapEditor`, `addTagInput`, `addSlideToConfirm` and `*Field` / `*FieldType` variants) are Composer **suggest** only: `class_exists` gate, `LogicException` when missing, `FormTypeMap` optional entries, no hard `require`.

### Multi-step wizard

- **FR-WIZ-001**: `MultiStepFormBuilder`, session, and factory for step persistence.

### Assets & Twig

- **FR-ASSET-001**: TypeScript help-modal + logger sources and built `help-modal.js` / `.css`.
- **FR-TWIG-001**: Form renderer, static blocks, and framework-specific help-modal shells.

---

## Success Criteria

- **SC-001**: **51/51** files mapped in inventory.
- **SC-002**: Config keys match [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md).
- **SC-003**: `composer qa` / CI green.
- **SC-004**: Bound-builder helpers and `resolveFieldOptions` are covered by unit tests; conditional-field patterns are documented in [`docs/USAGE.md`](../../docs/USAGE.md) and demonstrated in demos.
- **SC-005**: Optional nowo-tech helpers throw when the package is missing and add fields when stubs/classes exist; documented in USAGE / CONFIGURATION; demo `/nowo-special-fields` uses the helpers including TagInput and SlideToConfirm.

---

## Explicit non-goals

- Rendering forms outside Symfony Form component.
- Bundling optional third-party field types (FOS CKEditor 4, UX Autocomplete, Select All Choice, nowo-tech OTP/phone/password/icon/CKEditor 5/Tiptap/tag/slide widgets) — Composer **suggest** + helpers only; packages are not required.
- A first-class field option such as `when` / `visible_if` for show/hide — use Symfony `FormEvents`, build-time `if`, UI toggling, or Live Components (documented under Conditional fields in USAGE).

---

## Validation

`composer qa`, PHPUnit, PHPStan, inventory row audit.
