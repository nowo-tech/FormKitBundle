# Feature Specification: FormKitBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Status**: Active  
**Last reviewed**: 2026-10-29  

**Package**: `nowo-tech/form-kit-bundle`  
**Configuration root**: `nowo_form_kit`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Symfony bundle to **reduce repetitive form field options**: convention-based translation keys (`form_snake.field_snake.label|placeholder|help`), named YAML configs with cascading merge (global → field type → form → field), Phase-2 / bound-builder field helpers (`withBuilder` + `add*Field`), `resolveFieldOptions` for FormEvents-driven conditionals, CSS-framework-aware class utilities, optional help-modal UX, static form blocks, model transformers, multi-step wizard helpers, fail-closed CSRF action forms (`csrf_action_form()`), rootless GET filter forms, accessible per-locale tabs (`TranslatableMapType`, `locale_tabs`) with bundle translations in the `NowoFormKitBundle` domain, and an opt-in stateless CSRF token id for anonymous forms.

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

### US-08 — CSRF action forms fail closed (P1)

**Given** a host controller that renders a single POST action with `csrf_action_form('/items/1/delete', 'delete_item_1')` (or `CsrfOnlyFormFactory::createNamed()`), **When** the form is rendered and submitted, **Then** the view carries a `_token` field for that token id and `isValid()` only accepts a valid token. **And** when Symfony's form CSRF extension is unavailable (`symfony/security-csrf` missing, `framework.csrf_protection: false` or `framework.form.csrf_protection: false`), **Then** building `CsrfOnlyType` / `HiddenFieldsCsrfType` throws `LogicException` instead of silently accepting the POST (only an explicit `csrf_protection: false` from calling code disables the check).

### US-09 — Rootless GET filters (P2)

**Given** a list page with a search box, **When** the controller builds `GetFilterFormFactory::create(SearchQueryType::class, ['q' => $q])`, **Then** the form has no root name (query string `?q=…`), uses the `filter` profile (no required fields, convention placeholder/help) and submits with GET.

### US-10 — Locale tabs and bundle translations (P2)

**Given** `TranslatableMapType` (or any field with `locale_tabs: true`) and `framework.enabled_locales: [es, en]`, **When** the form renders, **Then** one WAI-ARIA tab per locale is shown, the default locale is marked, tabs with errors are marked and opened first, and the screen-reader hints come from the `NowoFormKitBundle` translation domain. **And** an application file `translations/NowoFormKitBundle.<locale>.yaml` overrides any of those keys while missing keys fall back to the bundle catalogue.

---

## Requirements

### Bundle & config

- **FR-BUNDLE-001**: `NowoFormKitBundle` alias `nowo_form_kit` and register `TwigPathsPass` for the `NowoFormKitBundle` Twig namespace.
- **FR-CFG-001**: `Configuration` — `type_map`, `default_profile`, `css_framework`, named `profiles` (alias, translation_domain, required_label_suffix, help_modal, defaults, field_types).
- **FR-CFG-002**: `FormKitExtension` loads services and parameters; legacy root keys normalize to `default` config.

### DI

- **FR-DI-001**: `Resources/config/services.yaml` wires merger, CSS utilities, extensions, and type map.
- **FR-DI-002**: `FormOptionsMergerInjectorCompilerPass` injects merger into tagged form types.
- **FR-DI-003**: `Compiler\LocaleTabsLocalesPass` fills `nowo_form_kit.locale_tabs.locales` from `kernel.enabled_locales` when not configured and `nowo_form_kit.locale_tabs.default_locale` from `kernel.default_locale` when null (compile time, only when the FrameworkBundle parameters exist).
- **FR-DI-004**: Bundle form types, type extensions and Twig extensions are registered with `autoconfigure: true` and tagged by Symfony autoconfiguration (`form.type`, `form.type_extension`, `twig.extension`); only the per-type stateless CSRF extensions keep an explicit `form.type_extension` tag with `extended_type` (REQ-SF-003).

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
- **FR-FORM-013**: Per-locale tabs — `LocaleTabsExtension` (`locale_tabs` option: `false` default | `true` | array) adds the `nowo_locale_tabs` block prefix before the unique prefix and a `locale_tabs` view var; `locale_tabs_theme.html.twig` / `_locale_tabs.html.twig` render one WAI-ARIA tab per locale (tablist/tab/tabpanel, `aria-controls`/`aria-labelledby`/`aria-selected`), inactive panels `hidden`, default locale marked, invalid tabs marked and opened first, child rows rendered once (`setRendered`); emits Stimulus `tabs` and UiKit `data-nowo-ui-tabs*` attributes (both toggleable); no inline script/style/handlers.
- **FR-FORM-014**: Opt-in `stateless_csrf` (`enabled: false` default, `token_id: submit`, `form_types`, `register_stateless_token_id: true`) registers one `StatelessCsrfTokenIdExtension` per listed type (tag `extended_type`) setting the `csrf_token_id` default, and prepends the id to `framework.csrf_protection.stateless_token_ids`; explicit options win.
- **FR-FORM-015**: `accessibility.unlabelled_choice_aria_label` (default `true`) registers `UnlabelledChoiceAriaLabelExtension` (extends `ChoiceType`, so subtypes such as `EntityType`); when disabled no service is registered. In `finishView()`, a non-expanded field whose view `label` is `false` and whose `attr` has no `aria-label` / `aria-labelledby` gets `attr.aria-label` = translated non-empty `help`, else translated non-empty `placeholder`, else nothing; `translation_domain: false` (or no translator) keeps the raw text; idempotent and stateless.
- **FR-FORM-016**: CSRF action forms — `CsrfOnlyType` (only a CSRF field, flat `_token` unless created named), `HiddenFieldsCsrfType` (CSRF + `fields` rendered as hidden widgets unless `field_types` / `field_options` say otherwise, flat submitted keys) and `CsrfOnlyFormFactory` (`create`, `createNamed`, `createWithFields`). `CsrfProtectionGuard` makes both types throw `LogicException` at build time when `csrf_protection` resolves to true but the form CSRF extension is not registered (fail closed).
- **FR-FORM-017**: GET filter forms — `AbstractGetFilterType` (`#[FormKitConfig('filter')]`, method GET, no CSRF, rootless), `GetFilterFormFactory::create()` (named `''` so field names stay query-string friendly) and `SearchQueryType` (`q` search field; requires `search` in `FormTypeMap`).
- **FR-FORM-018**: `CheckboxRowExtension` (extends `CheckboxType`) exposes `checkbox_bootstrap` (profile / root `css_framework` is Bootstrap → `form-check` classes) and `checkbox_row_class` (field row class, else the profile `defaults.row_attr.class`, else `mb-3` on Bootstrap) to the `checkbox_row` block of `static_blocks.html.twig`; stateless.
- **FR-FORM-019**: `TranslatableMapType` edits a `{locale: text}` map (array or JSON string with `input: json`) as one child per locale (`locales`, `default_locale`, `required_locales` → required + `NotBlank`, `entry_type` / `entry_options`, `remove_empty`), keeps model keys outside `locales` on submit, and enables `locale_tabs` by default.

### Multi-step wizard

- **FR-WIZ-001**: `MultiStepFormBuilder`, session, and factory for step persistence.
- **FR-WIZ-002**: `MultiStepWizardSession` resolves the session from `RequestStack` on every access (FrankenPHP worker / `FRANKENPHP_RESET_KERNEL` unset|false safe); create via factory inside the request action.

### FrankenPHP worker

- **FR-WORKER-001**: Bundle container services hold no per-request mutable state; no `kernel.reset` required for Form Kit services when the kernel is reused between requests.
- **FR-WORKER-002**: Trait setters (`setFormKitFormName`, `setFormKitConfigName`, translations) are DI/constructor configuration only; documented in USAGE and `docs/FRANKENPHP-WORKER-AUDIT.md`.
- **FR-WORKER-003**: PHPStan includes `nowo-tech/phpstan-frankenphp` classic + worker rulesets (`phpstan.neon.dist`).

### Assets & Twig

- **FR-ASSET-001**: TypeScript help-modal + logger sources and built `help-modal.js` / `.css`.
- **FR-ASSET-002**: `FormKitExtension::prepend()` registers the `nowo_form_kit` asset package (`base_path: /bundles/nowoformkit`) when FrameworkBundle is enabled; `symfony/asset` is a Composer **require** (not suggest) so that configuration always boots.
- **FR-ASSET-003**: Opt-in Stimulus peer `stimulus-peers/combobox_controller.ts` (identifier `combobox`): ARIA 1.2 editable combobox with list autocomplete over static options, keyboard navigation, neutral default required message (`requiredMessage` value). Not built or registered by the bundle; `@hotwired/stimulus` is a host dependency. Detail: [`../003-combobox-peer/spec.md`](../003-combobox-peer/spec.md).
- **FR-TWIG-002**: `CsrfActionFormExtension` registers Twig function `csrf_action_form(action, csrfTokenId, method = 'POST', named = true, csrfFieldName = '_token', fields = {}, fieldTypes = {}, fieldOptions = {})` returning a `FormView` from `CsrfOnlyFormFactory` (`fields` non-empty → `createWithFields`, else `named` → `createNamed`, else `create`); registered (tag `twig.extension`) only when Twig is installed and `twig.csrf_action_form` is `true` (default). Detail: [`../002-csrf-action-form/spec.md`](../002-csrf-action-form/spec.md).
- **FR-TWIG-001**: Form renderer, static blocks, and framework-specific help-modal shells. `form_label_content` must treat `required` as optional (Submit/Button/Reset widgets do not define it) before appending `required_label_suffix`.
- **FR-TWIG-003**: `LocaleTabsTwigExtension` registers `nowo_form_kit_locale_tabs(field, options)` (groups child views per locale in `children`, `entries` or `fields` mode, marks invalid leaves with `aria-invalid`) and `nowo_form_kit_ui_kit_locale_tabs()` (UiKit 2.5+ partial when installed, else null → neutral markup), configured by `nowo_form_kit.locale_tabs.*`.
- **FR-BUILD-001**: `src/Resources/public/help-modal.js` is the committed Vite build output (IIFE, deterministic build id) of `src/Resources/assets/src/help-modal.ts` + `logger.ts`; it is rebuilt with `make assets` and never edited by hand.

### Security

- **FR-SEC-001**: State-changing bundle form types fail closed when form CSRF is unavailable (FR-FORM-016); developer-controlled HTML options (`help_modal` title/content/icon, `input_group_prefix` / `_suffix`) are documented as trusted markup; the help modal only processes labels inside a `<form>` ([`docs/SECURITY.md`](../../docs/SECURITY.md)).

### Translations

- **FR-I18N-001**: Bundle UI strings (`locale_tabs.label`, `.default`, `.required`, `.invalid`) ship in domain `NowoFormKitBundle` for `en`, `es`, `fr`, `de`, `it`, `pt`, `nl` with identical keys (`make validate-translations`); application catalogues `translations/NowoFormKitBundle.<locale>.yaml` override them key by key ([`docs/USAGE.md`](../../docs/USAGE.md#overriding-the-bundle-messages-nowoformkitbundle-domain)).

---

## Success Criteria

- **SC-001**: **77/77** production files mapped in [`code-inventory.md`](code-inventory.md) (`find src -type f ! -name '*.test.ts' | wc -l` = 77; the 3 co-located Vitest files are excluded).
- **SC-002**: Config keys match [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md).
- **SC-003**: `composer qa` / CI green.
- **SC-004**: Bound-builder helpers and `resolveFieldOptions` are covered by unit tests; conditional-field patterns are documented in [`docs/USAGE.md`](../../docs/USAGE.md) and demonstrated in demos.
- **SC-005**: Optional nowo-tech helpers throw when the package is missing and add fields when stubs/classes exist; documented in USAGE / CONFIGURATION; demo `/nowo-special-fields` uses the helpers including TagInput and SlideToConfirm.
- **SC-006**: FrankenPHP worker audit (`docs/FRANKENPHP-WORKER-AUDIT.md`) documents compatibility with kernel reuse (`FRANKENPHP_RESET_KERNEL` unset/false); PHPStan classic+worker rulesets green.

---

## Explicit non-goals

- Rendering forms outside Symfony Form component.
- Bundling optional third-party field types (FOS CKEditor 4, UX Autocomplete, Select All Choice, nowo-tech OTP/phone/password/icon/CKEditor 5/Tiptap/tag/slide widgets) — Composer **suggest** + helpers only; packages are not required.
- A first-class field option such as `when` / `visible_if` for show/hide — use Symfony `FormEvents`, build-time `if`, UI toggling, or Live Components (documented under Conditional fields in USAGE).

---

## Validation

`composer qa`, `make release-check` (CS, Rector, PHPStan, Igor, `validate-translations`, coverage ≥ 99%, demo checks, Vitest), inventory row audit (SC-001).

See also [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/USAGE.md`](../../docs/USAGE.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md) and [`docs/CSRF.md`](../../docs/CSRF.md).
