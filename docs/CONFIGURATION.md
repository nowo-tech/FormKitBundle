# Configuration

The bundle is configured under the root key `nowo_form_kit`. Multiple profiles can coexist; each is identified by a name and has an `alias` and the usual options.

## Table of contents

- [Structure](#structure)
- [Cascade order](#cascade-order)
- [Per-form defaults (`by_form`)](#per-form-defaults-by_form)
- [Form type extensions (global)](#form-type-extensions-global)
- [Stateless CSRF for public forms (`stateless_csrf`)](#stateless-csrf-for-public-forms-stateless_csrf)
- [Accessibility (`accessibility`)](#accessibility-accessibility)
- [Twig helpers (`twig`)](#twig-helpers-twig)
- [Money fields (`addMoneyType`)](#money-fields-addmoneytype)
- [Example with multiple profiles](#example-with-multiple-profiles)
- [Suggested optional Composer packages](#suggested-optional-composer-packages)
  - [nowo-tech widgets supported](#nowo-tech-widgets-supported)
- [Optional and custom types (type_map)](#optional-and-custom-types-type_map)
- [Using a specific profile in a form type](#using-a-specific-profile-in-a-form-type)

## Structure

| Option | Type | Description |
|--------|------|-------------|
| `default_profile` | `string` | Name (key) of the profile to use when a form does not specify one. Must be a key in `profiles`. Default: `default`. |
| `profiles` | `array` | Named profiles. Key = profile name (e.g. `default`, `bootstrap`). Each value has: |
| `profiles.<name>.alias` | `string` | **Required.** Alias for this profile (e.g. for reference in form types or UI). |
| `profiles.<name>.translation_domain` | `string` | Translation domain for labels, placeholders, help. Default: `messages`. |
| `profiles.<name>.convention_requires_translation` | `bool\|null` | When `true`, the auto **help** and auto **placeholder** convention keys (`{form}.{field}.help` / `.placeholder`) are only applied if the key exists in the translator catalogue (current locale, fallback locales included) for the field's translation domain. Labels are never affected; explicit / configured values always win. `null` (default) inherits the root `convention_requires_translation` (default `false` = always apply the keys). See [Convention keys only when translated](#convention-keys-only-when-translated-convention_requires_translation). |
| `profiles.<name>.required_label_suffix` | `string\|null` | Appended to the label when the field is required (e.g. ` *`). `null` or empty disables. **`RequiredLabelSuffixExtension`** reads this from the profile whose key equals **`default_profile`** (not from the form’s `setFormKitConfigName()`), and injects `required_label_suffix` into the view for all forms. The `static_blocks` theme skips the suffix when `required` is not in the block scope (Submit/Button/Reset). |
| `profiles.<name>.defaults.attr` | `array` | Default HTML attributes for every field (e.g. `class: form-control`). |
| `profiles.<name>.defaults.row_attr` | `array` | Default HTML attributes for the form row wrapper (e.g. `class: mb-3`). |
| `profiles.<name>.defaults.help_attr` | `array` | Default HTML attributes for field help (`help_attr`, e.g. `class` for muted small copy). |
| `profiles.<name>.help_attr` | `array` | Since 2.13: shorthand for `defaults.help_attr` at profile level (e.g. `{class: form-text}`); `defaults.help_attr` wins per attribute. Attribute names are kept as written (`data-*`). |
| `profiles.<name>.css_framework` | `string\|null` | Since 2.13: framework of this profile's form-theme chrome (`static_blocks.html.twig` `checkbox_row`): Bootstrap (`bootstrap`, `bootstrap5`…) adds `form-check` / `form-check-input` / `form-check-label` and the `mb-3` row fallback; any other value renders the checkbox row with the profile's `defaults.row_attr.class` only. Null (default) = root `css_framework`. |
| `profiles.<name>.defaults.label` | `string\|bool` | Optional. Default `label` for every field (e.g. `false` to suppress). When set, overrides the `{form}.{field}.label` convention. Overridable per `field_types` / `by_form` / PHP. |
| `profiles.<name>.defaults.placeholder` | `string\|bool` | Optional. Default HTML `placeholder` (e.g. `false` to suppress). When set, overrides the `auto_placeholder` convention key. Not applied to `ChoiceType` fields (their `placeholder` is the empty option; set it in `field_types.choice` / `by_form.<form>.fields` / PHP instead). See [Placeholder semantics](#placeholder-semantics). |
| `profiles.<name>.defaults.help` | `string\|bool` | Optional. Default `help` (e.g. `false` to suppress). When set, overrides `auto_help`. |
| `profiles.<name>.defaults.required` | `bool` | Optional. Default `required` for every field. Overridable per `field_types` / `by_form` / PHP. |
| `profiles.<name>.field_types` | `array` | Per-field-type default options. Key = short type name (e.g. `text`, `email`) or FQCN. Value = options array (may include `label`, `placeholder`, `help`, `required`, `constraints`). |
| `profiles.<name>.by_form` | `array` | Per-form defaults keyed by form name / block prefix (e.g. `user_profile`). Each entry may set `defaults.attr` / `defaults.row_attr` / `defaults.help_attr` / `defaults.label` / `defaults.required` (and peers) and `fields.<field>` overrides (including `constraints`). Merged **after** `field_types`, **before** per-field options. |
| `profiles.<name>.constraint_message_convention` | `bool` | When `true`, constraints without an explicit `message` (or `minMessage`/`maxMessage` when `min`/`max` are set) get keys `{form}.{field}.constraints.{Name}` (and `.min` / `.max` suffixes for Length-style). Put those keys in the **validators** catalog. Default: `false`. |
| `profiles.<name>.help_modal` | `array` | Default options when a field sets `help_modal: true` (merged with per-field overrides). Keys: `framework` (`bootstrap5`, `bootstrap4`, `tailwind`, `foundation`), `icon_html`, optional `ux_icon` / `ux_icon_attributes` (with **symfony/ux-icons**), `trigger_class`, `aria_label`, `title` / `title_html`, `content` (plain text) / `content_html` (trusted HTML). See [Usage — Help modal](USAGE.md#help-modal-optional). |
| `convention_requires_translation` | `bool` | Global default for `profiles.<name>.convention_requires_translation` (also used by the built-in `filter` profile). Default: `false`. |
| `css_framework` | `string` | CSS framework used by the `Nowo\FormKitBundle\Css\CssClassUtilities` service (inject it in your code): `normalizeColumnClasses()` / `normalizeColumnClassesFromString()` keep one grid column class per breakpoint (widest wins) in breakpoint order, `orderClasses()` deduplicates and sorts a class string by category (layout → flex/grid → spacing → …). One of `bootstrap` (default), `tailwind` (`col-span-*` / `md:col-span-*`), `foundation` (`small-*` / `medium-*` …), `none` (deduplicate only; no column merge, no ordering). Any other value fails container compilation. Does not change the bundle form themes or profile `attr` merging. |
| `stateless_csrf.enabled` | `bool` | Opt-in (default `false`). Give the listed form types a stateless CSRF token id. See [below](#stateless-csrf-for-public-forms-stateless_csrf). |
| `stateless_csrf.token_id` | `string` | `csrf_token_id` default applied to the listed types. Default `submit`. |
| `stateless_csrf.form_types` | `list<string>` | Form type FQCNs (exact types, not their children). Default `[]`. Symfony base/core types (`FormType`, anything under `Symfony\Component\Form\` or `Symfony\Bridge\`) are rejected. |
| `stateless_csrf.register_stateless_token_id` | `bool` | Prepend `token_id` to `framework.csrf_protection.stateless_token_ids`. Default `true`. |
| `accessibility.unlabelled_choice_aria_label` | `bool` | Default `true`. Give non-expanded choice fields rendered with `label: false` an `aria-label` from their help, else their placeholder. See [below](#accessibility-accessibility). |
| `locale_tabs.locales` | `list` | Since 2.13. Locales of `TranslatableMapType` and of `fields`-mode locale tabs without `locales`. Default `[]` = `framework.enabled_locales` (else the default locale). |
| `locale_tabs.default_locale` | `string\|null` | Since 2.13. Default language (marked, listed first). Default `null` = `kernel.default_locale` when it is one of the locales, else the first locale. |
| `locale_tabs.renderer` | `auto\|ui_kit\|native` | Since 2.13. `auto` (default): UiKit's `locale_tabs` partial when nowo-tech/ui-kit-bundle 2.5+ is installed, else the neutral markup; `native`: always the neutral markup; `ui_kit` behaves like `auto`. |
| `locale_tabs.form_theme` | `bool` | Since 2.13. Default `true`: registers `@NowoFormKitBundle/form/locale_tabs_theme.html.twig` in `twig.form_themes` (it only renders fields with `locale_tabs`). |
| `twig.csrf_action_form` | `bool` | Default `true`. Register the `csrf_action_form()` Twig function (only when Twig is installed). See [below](#twig-helpers-twig). |
| `type_map` | `array` | Additional form type names (snake_case) => FQCN. Merged with built-in and optional types (e.g. Dropzone, Cropper, A2lix Translations when the package is installed). Use for custom types or to override. |

**Legacy:** If `profiles` is not set (or empty), the root-level `translation_domain`, `required_label_suffix`, `defaults`, `help_modal`, `field_types`, `by_form`, and `constraint_message_convention` are used to build a single profile named `default`, so existing YAML keeps working. YAML keys `default_config` / `configs` are still accepted and normalized to `default_profile` / `profiles` (see [UPGRADING](UPGRADING.md#2011-2026-07-18)).

## Cascade order

For each field, `FormOptionsMerger::resolve()` merges:

1. Convention keys (`label` / `help` / `attr.placeholder`) + profile `defaults`
2. `field_types.<type>`
3. `by_form.<formName>.defaults`
4. `by_form.<formName>.fields.<fieldName>`
5. Options passed in PHP / `add*()`

Later layers win (scalars replace; nested arrays merge recursively). Constraints are collected from layers 2–5 and instantiated last.

### Placeholder semantics

The `{form}.{field}.placeholder` convention key (`auto_placeholder: true`) is only a **fallback**: explicit and configured values always win over it.

- **Text-like fields** (text, email, textarea, number, date…): the root `placeholder` option is moved to `attr.placeholder`. Precedence: PHP `placeholder: false` (removes it) → PHP `attr.placeholder` → PHP `placeholder` → configured `placeholder: false` → configured `attr.placeholder` → configured `placeholder` (profile / `field_types` / `by_form`) → convention key.
- **`ChoiceType` and subtypes** (`EntityType`, `EnumType`, `CountryType`, UX Autocomplete types, your own types whose parent chain reaches `ChoiceType`): the root `placeholder` is the **empty-option label** and stays at the root. `placeholder: false` is kept, so no empty option is rendered (also for non-required expanded radios: no "None" choice). Without a field-specific `placeholder`, Symfony's default applies (empty option only when `required: false`). Only PHP options, `by_form.<form>.fields.<field>` and `field_types.<type>` set it; the generic `defaults.placeholder` (profile or `by_form`) is ignored for choices. No convention key is added (neither root nor `attr`).
- **Checkbox, radio and hidden fields** (and subtypes) never get the convention key; an explicit `placeholder` / `attr.placeholder` is still honoured.

Subtypes are detected through the Symfony form registry (`form.registry`) and the `type_map`; without a registry, the built-in short names / FQCNs are recognised.

### Convention keys only when translated (`convention_requires_translation`)

By default every text-like field gets `help: {form}.{field}.help` and `attr.placeholder: {form}.{field}.placeholder`, so a field without those translations renders the raw key. Set `auto_help` / `auto_placeholder` to `false` to drop them everywhere, or opt in to `convention_requires_translation` to keep them **only where a translation exists**:

```yaml
nowo_form_kit:
    convention_requires_translation: true    # global default for every profile
    profiles:
        default:
            alias: default
            translation_domain: forms
        legacy:
            alias: legacy
            convention_requires_translation: false   # per-profile override
```

```yaml
# translations/forms.es.yaml
contact:
    name:
        label: Nombre
        help: Tu nombre completo      # help applied; no placeholder key → no attr.placeholder
```

How it works and caveats:

- The check is `TranslatorBagInterface::getCatalogue()->has($key, $domain)`: the **current translator locale** plus its **fallback catalogues**, and the field's effective `translation_domain` (a per-field `translation_domain` override is honoured; `translation_domain: false` means "not translated", so the keys are skipped).
- **Labels are never affected** — they always get `{form}.{field}.label`.
- **Explicit options always win:** a `help` / `placeholder` / `attr.placeholder` set in PHP, `defaults`, `field_types` or `by_form` is applied as-is, translated or not. `auto_help: false` / `auto_placeholder: false` still disable the keys entirely.
- **Translator is optional:** the bundle injects `@?translator`. Without a translator, or with one that does not implement `TranslatorBagInterface` (e.g. `IdentityTranslator` when `framework.translator` is disabled), the flag has **no effect** and the keys are applied as before.
- **Locale at build time vs render time:** the decision is taken when the form is built (`FormOptionsMerger::resolve()`), using the translator locale at that moment. If you build a form in one locale and render it in another (switching `LocaleSwitcher` in between, building before the locale listener runs, or in console / messenger contexts without a request locale), a key may be kept or dropped according to the build locale. Configure fallback locales so every key you rely on exists in at least one of them.
- **Profiler:** fields whose help / placeholder key is dropped no longer render the key, so those keys stop appearing as *missing* messages in the Symfony translation profiler panel. Use `bin/console debug:translation <locale> --only-missing`, or keep the flag off in development, if you rely on the profiler to find untranslated help / placeholders.
- **Long-running workers** (FrankenPHP / RoadRunner): the merger keeps no per-request state; the catalogue is looked up on every `resolve()` through the translator, so locale changes between requests are honoured.

## Per-form defaults (`by_form`)

```yaml
nowo_form_kit:
    profiles:
        default:
            alias: default
            translation_domain: messages
            defaults:
                attr: { class: 'form-control' }
                row_attr: { class: 'mb-3' }
            by_form:
                user_profile:
                    defaults:
                        row_attr: { class: 'col-12 mb-4' }
                    fields:
                        email_address:
                            attr: { autocomplete: email }
                            constraints: [ NotBlank, Email ]
            constraint_message_convention: true
```

With `constraint_message_convention: true`, `NotBlank` without an explicit message uses the key `user_profile.email_address.constraints.NotBlank` (add it under `translations/validators.*.yaml` or your validators domain).

## Form type extensions (global)

The bundle registers these **form type extensions** (they apply to all field types unless the option is unused):

| Extension | Purpose |
|-----------|---------|
| **InputGroupExtension** | Options `input_group_prefix` and `input_group_suffix` for Bootstrap-style input groups (requires the bundle form theme; see [Usage](USAGE.md#input-group-icon-at-start-or-end)). |
| **RequiredLabelSuffixExtension** | Appends `required_label_suffix` from the profile named **`default_profile`** to required field labels. The `static_blocks` theme does not apply it to Submit/Button/Reset (`required` is undefined in `form_label_content`). |
| **HelpModalExtension** | Option `help_modal` (`false`, `true`, or array): injects JSON (with the per-request nonce) into `label[data-nowo-help-modal]` for the frontend script; the layout prints the nonce with `nowo_form_kit_help_modal_meta()`; see [Usage](USAGE.md#help-modal-optional). |
| **LocaleTabsExtension** | Option `locale_tabs` (`false` default, `true`, or array of partial options): adds the `nowo_locale_tabs` block prefix + `locale_tabs` view var so `locale_tabs_theme.html.twig` renders one tab per locale; see [Usage](USAGE.md#locale-tabs-one-tab-per-language). |
| **CheckboxRowExtension** | Since 2.13 (CheckboxType): `checkbox_bootstrap` / `checkbox_row_class` view vars from the default profile (`css_framework`, `defaults.row_attr.class`) for the `checkbox_row` block of `static_blocks.html.twig`. |
| **StatelessCsrfTokenIdExtension** | Only when `stateless_csrf.enabled`: one service per listed form type (tag attribute `extended_type`) setting the `csrf_token_id` default. |
| **UnlabelledChoiceAriaLabelExtension** | Only when `accessibility.unlabelled_choice_aria_label` (default `true`): names label-less selects from their help / placeholder. See [below](#accessibility-accessibility). |

## Stateless CSRF for public forms (`stateless_csrf`)

Symfony stores per-form CSRF tokens (token id = form name) in the **session**. Rendering an anonymous public form (cookie-consent modal, newsletter, comment form…) therefore starts a session on every page view, writes it to the session store and makes the response `private` (no HTTP cache). Token ids listed in `framework.csrf_protection.stateless_token_ids` are validated with the `Origin`/`Referer` header and a double-submit cookie instead.

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    stateless_csrf:
        enabled: true
        # token_id: submit                    # default; matches the Symfony recipe
        # register_stateless_token_id: true   # adds token_id to framework.csrf_protection.stateless_token_ids
        form_types:
            - App\Form\NewsletterType
            - Nowo\CookieConsentBundle\Form\CookieConsentType
```

- Off by default; with `enabled: false` nothing is registered and `form_types` is ignored.
- Only the exact listed types are affected (Symfony applies type extensions per type, not to subclasses).
- An explicit `csrf_token_id` passed to `createForm()` / set in the type's own options at creation time still wins.
- Keep **authenticated / state-changing back-office forms** on the default session-backed tokens; use this for anonymous forms only.
- Stateless validation needs `Origin`/`Referer` headers from browsers (or the recipe's `csrf_protection_controller.js` for the double-submit cookie). See [CSRF.md](CSRF.md#stateless-csrf-for-public-forms).

## Accessibility (`accessibility`)

Compact filter bars usually render selects with `label: false` (the built-in `filter` profile does), which leaves them without an accessible name: screen readers announce only "combo box, Any level". `UnlabelledChoiceAriaLabelExtension` fixes that at render time.

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    accessibility:
        unlabelled_choice_aria_label: true   # default; false = the extension is not registered
```

Rules (applied in `finishView()` to `ChoiceType` and every type extending it, e.g. `EntityType`, `EnumType`, `CountryType`):

- Only when the view `label` is `false` **and** the field is not `expanded` (radios/checkboxes keep their per-option labels and a fieldset legend is the right fix there).
- Skipped when `attr` already has `aria-label` or `aria-labelledby` — explicit names always win, and the extension is idempotent.
- `aria-label` = translated `help` if it is a non-empty string, else translated `placeholder` (the empty option), else nothing. The field's `translation_domain` is used; `translation_domain: false` keeps the raw text. Without a translator service the raw text is used.
- Stateless (only reads the view), safe in FrankenPHP worker mode.

## Twig helpers (`twig`)

Since **2.11.0**. `CsrfActionFormExtension` (tag `twig.extension`) provides the `csrf_action_form()` Twig function on top of `CsrfOnlyFormFactory`. Usage: [CSRF.md](CSRF.md#twig-function-csrf_action_form).

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    twig:
        csrf_action_form: true   # default; false = the extension is not registered
```

- Registered only when `twig/twig` is installed (it is, through `twig/extra-bundle`).
- Twig does not throw on duplicate function names: if the app also defines `csrf_action_form`, the extension registered last wins (app services usually load after bundle services). Remove the app function to adopt the bundle one, or set `false` to keep yours.
- Stateless; safe in FrankenPHP worker mode.

## Money fields (`addMoneyType`)

`addMoneyType()` / `addMoneyField()` render a text input and attach `MoneyModelTransformer` (field option `money_scale`, default `2`; `required` defaults to `false`).

| Direction | Input | Result |
|-----------|-------|--------|
| Model → view (`transform`) | `int` or integer string (`1234`, `"1234"`) | Minor units / cents: `"12.34"` with scale 2. |
| Model → view | `float` or decimal string (`12.345`, `"12.345"`) | The human amount, rounded to `scale`: `"12.35"`. |
| Model → view | `null` / empty string | `""` / `"0.00"`. |
| View → model (`reverseTransform`) | `"12.34"`, `"12,34"`, `"1 234.5"` | Always `int` minor units (`1234`, `123450`); empty input → `null`. Invalid text → transformation failure (field error). |

Rounding: extra decimals beyond `scale` are rounded **half up** (away from zero for negatives) for every scale, including `money_scale: 0` (`"12.5"` → `13`, `"12.49"` → `12`). With `money_scale: 0` the model is the whole amount (no minor units).

Watch out for integer strings: `"1234"` in the model is read as cents (`"12.34"`), while `"1234.0"` is read as an amount (`"1234.00"`). Persist money as `int` cents to avoid the ambiguity.

## Example with multiple profiles

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    default_profile: default
    profiles:
        default:
            alias: default
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control'
                row_attr:
                    class: 'mb-3'
            field_types:
                text:
                    attr: { class: 'form-control' }
        bootstrap:
            alias: bootstrap
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control form-control-lg'
                row_attr:
                    class: 'mb-3'
            field_types: {}
```

## Suggested optional Composer packages

The bundle core has no hard dependency on UX or third-party form integrations. Composer **`suggest`** entries document optional packages and the corresponding helpers:

| Suggested package | Purpose |
|-------------------|---------|
| **a2lix/translation-form-bundle** | `translations` type in FormTypeMap; `addTranslations()` on traits |
| **nowo-tech/select-all-choice-bundle** | `addMultiSelectSelectAll()` / `addMultiSelectSelectAllType()` (`select_all` on `ChoiceType` multiple) |
| **nowo-tech/otp-input-bundle** | `addOtp()` / `addOtpField()` / `addOtpFieldType()` (`otp` in FormTypeMap) |
| **nowo-tech/phone-input-bundle** | `addPhone()` / `addPhoneField()` / `addPhoneFieldType()` (`phone`) |
| **nowo-tech/password-toggle-bundle** | `addPasswordToggle()` / `addPasswordToggleField()` / `addPasswordToggleFieldType()` (`password_toggle`; distinct from Symfony `addPassword()`) |
| **nowo-tech/password-strength-bundle** | `addPasswordStrength()` / `addPasswordStrengthField()` / `addPasswordStrengthFieldType()` (`password_strength`) |
| **nowo-tech/icon-selector-bundle** | `addIconSelector()` / `addIconSelectorField()` / `addIconSelectorFieldType()` (`icon_selector`) |
| **nowo-tech/ckeditor5-editor-bundle** | `addCkeditor5Editor()` / `addCkeditor5EditorField()` / `addCkeditor5EditorFieldType()` (`ckeditor5`; distinct from FOS `addCKEditorField()`) |
| **nowo-tech/tiptap-editor-bundle** | `addTiptapEditor()` / `addTiptapEditorField()` / `addTiptapEditorFieldType()` (`tiptap`) |
| **nowo-tech/milkdown-editor-bundle** | `addMilkdownEditor()` / `addMilkdownEditorField()` / `addMilkdownEditorFieldType()` (`milkdown`; Markdown) |
| **nowo-tech/tag-input-bundle** | `addTagInput()` / `addTagInputField()` / `addTagInputFieldType()` (`tag`) |
| **nowo-tech/slide-to-confirm-bundle** | `addSlideToConfirm()` / `addSlideToConfirmField()` / `addSlideToConfirmFieldType()` (`slide_to_confirm`) |
| **nowo-tech/altcha-type-bundle** | `addAltcha()` / `addAltchaField()` / `addAltchaFieldType()` (`altcha`) |
| **nowo-tech/barcode-input-bundle** | `addBarcode()` / `addBarcodeField()` / `addBarcodeFieldType()` (`barcode`) |
| **nowo-tech/ui-kit-bundle** | ^2.15.0: `addDateRange()` / `addDateRangeField()` / `addDateRangeFieldType()` (`date_range`); ^2.5.3: locale tabs through the UiKit partial |
| **friendsofsymfony/ckeditor-bundle** | `addCKEditorField()` / `addCKEditorFieldType()` (CKEditor 4) |
| **symfony/ux-autocomplete** | Use with `addAutocompleteField()` / `addAutocompleteFieldType()` and your autocomplete FormType FQCN |
| **symfony/ux-dropzone** | `addDropzone()` / `addDropzoneField()` / `addDropzoneFieldType()` |
| **symfony/ux-cropperjs** | `addCropper()` / `addCropperField()` / `addCropperFieldType()` |
| **symfony/ux-live-component** | Optional Live forms; demo `/conditional-fields-live` |
| **symfony/ux-icons** | `help_modal.ux_icon` in field options (IconRenderer) instead of raw `icon_html` only |

Install only what you need; each helper documents runtime requirements (e.g. `LogicException` if a suggested class is missing).

### nowo-tech widgets supported

| Map key | Composer package | Form type | `FormKitTrait` / `FormOptionsTrait` | `FormKitControllerTrait` | Notes |
|---------|------------------|-----------|-------------------------------------|--------------------------|-------|
| `otp` | nowo-tech/otp-input-bundle | `Nowo\OtpInputBundle\Form\OtpType` | `addOtp()` / `addOtpField()` | `addOtpFieldType()` | |
| `phone` | nowo-tech/phone-input-bundle | `Nowo\PhoneInputBundle\Form\Type\PhoneType` | `addPhone()` / `addPhoneField()` | `addPhoneFieldType()` | Country picker labels use domain `messages` |
| `password_toggle` | nowo-tech/password-toggle-bundle | `Nowo\PasswordToggleBundle\Form\Type\PasswordType` | `addPasswordToggle()` / `addPasswordToggleField()` | `addPasswordToggleFieldType()` | Distinct from Symfony `addPassword()` |
| `password_strength` | nowo-tech/password-strength-bundle | `Nowo\PasswordStrengthBundle\Form\PasswordStrengthType` | `addPasswordStrength()` / `addPasswordStrengthField()` | `addPasswordStrengthFieldType()` | |
| `icon_selector` | nowo-tech/icon-selector-bundle | `Nowo\IconSelectorBundle\Form\IconSelectorType` | `addIconSelector()` / `addIconSelectorField()` | `addIconSelectorFieldType()` | |
| `ckeditor5` | nowo-tech/ckeditor5-editor-bundle | `Nowo\Ckeditor5EditorBundle\Form\Ckeditor5EditorType` | `addCkeditor5Editor()` / `addCkeditor5EditorField()` | `addCkeditor5EditorFieldType()` | Distinct from FOS `addCKEditorField()` (CKEditor 4) |
| `tiptap` | nowo-tech/tiptap-editor-bundle | `Nowo\TiptapEditorBundle\Form\TiptapEditorType` | `addTiptapEditor()` / `addTiptapEditorField()` | `addTiptapEditorFieldType()` | |
| `milkdown` | nowo-tech/milkdown-editor-bundle | `Nowo\MilkdownEditorBundle\Form\MilkdownEditorType` | `addMilkdownEditor()` / `addMilkdownEditorField()` | `addMilkdownEditorFieldType()` | Markdown value; options `profile`, `features`, `theme`, `min_height`, `placeholder`, `suggestions`, `upload_url`, `collaboration` (see the bundle docs) |
| `tag` | nowo-tech/tag-input-bundle | `Nowo\TagInputBundle\Form\TagType` | `addTagInput()` / `addTagInputField()` | `addTagInputFieldType()` | Do not pass `placeholder => false` |
| `slide_to_confirm` | nowo-tech/slide-to-confirm-bundle | `Nowo\SlideToConfirmBundle\Form\Type\SlideToConfirmType` | `addSlideToConfirm()` / `addSlideToConfirmField()` | `addSlideToConfirmFieldType()` | `mapped: false` by default |
| `altcha` | nowo-tech/altcha-type-bundle | `Nowo\AltchaTypeBundle\Form\Type\AltchaType` | `addAltcha()` / `addAltchaField()` | `addAltchaFieldType()` | ALTCHA proof-of-work; `mapped: false`, `label: false` by default; options `profile`, `floating`, `hide_logo`, `hide_footer`, `challenge_url` |
| `barcode` | nowo-tech/barcode-input-bundle | `Nowo\BarcodeInputBundle\Form\BarcodeType` | `addBarcode()` / `addBarcodeField()` | `addBarcodeFieldType()` | Text input with camera scanner; options `enable_scanner`, `formats`, `max_length`, `facing_mode`, `trim_whitespace` |
| `date_range` | nowo-tech/ui-kit-bundle (^2.15.0) | `Nowo\UiKitBundle\Form\DateRangeType` | `addDateRange()` / `addDateRangeField()` | `addDateRangeFieldType()` | From/to filter field (`['from' => ?string, 'to' => ?string]`); options `from_name`, `to_name`, `placeholder`, `picker` |

Each helper throws `LogicException` when its package is not installed; on `FormKitTrait` the map key can also be
remapped in `nowo_form_kit.type_map`. `FormTypeMap::NOWO_WIDGETS` is the central list of these widgets and a test
fails when one is missing from the map, the Composer `suggest`, the helpers or this table. The **Select all** choice
(`nowo-tech/select-all-choice-bundle`) is a `ChoiceType` extension, not a map key: use
`addMultiSelectSelectAll()` / `addMultiSelectSelectAllField()` / `addMultiSelectSelectAllType()`.

Editors (CKEditor 5, Tiptap, Milkdown) inside locale tabs or other panels mount when their panel shows; see
[Locale tabs](USAGE.md#locale-tabs-one-tab-per-language).

## Optional and custom types (type_map)

The bundle registers a **FormTypeMap** that resolves snake_case type names to form type FQCNs.

**Built-in (always):** `text`, `email`, `textarea`, `password`, `url`, `integer`, `number`, `checkbox`, `choice`, `date`, `datetime`, `time`, `birthday`, `week`, `money`, `percent`, `currency`, `locale`, `language`, `country`, `timezone`, `file`, `hidden`, `tel`, `color`, `range`, `collection`, `repeated`, `submit`, `button`, `reset`, `date_interval`.

**Optional (when the class exists):** `dropzone` (UX Dropzone), `cropper` (UX Cropper.js), `translations` (A2lix), `otp`, `phone`, `password_toggle`, `password_strength`, `icon_selector`, `ckeditor5`, `tiptap`, `milkdown`, `tag`, `slide_to_confirm`, `altcha`, `barcode`, `date_range` (nowo-tech widgets; see [nowo-tech widgets supported](#nowo-tech-widgets-supported)). You can add more in config:

```yaml
nowo_form_kit:
    type_map:
        my_upload: 'App\Form\Type\MyUploadType'
```

This is used with the **Kit** strategy (`FormKitTrait` / `FormKitAbstractType`, e.g. `addField($builder, 'file', 'dropzone', [])` or `buildFormFromArray()` with snake_case types). See [Usage — strategies](USAGE.md#usage-strategies).

## Using a specific profile in a form type

Inject the merger and call `setFormKitConfigName('bootstrap')` (or the profile name you want) so that form uses that profile instead of `default_profile`. For example in `config/services.yaml`:

```yaml
App\Form\MyBootstrapFormType:
    tags: ['form.type']
    calls:
        - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
        - setFormKitConfigName: ['bootstrap']
```

Convention-based keys (label, placeholder, help) are derived from `{form_snake}.{field_snake}.label`, etc., unless you pass `label: false`, `placeholder: false` or `help: false` in the field options — or set the same keys once under `profiles.<name>.defaults` (or `by_form.<form>.defaults`).
