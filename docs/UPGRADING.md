# Upgrading

This document describes how to upgrade between major versions of Form Kit Bundle.

## Table of contents

- [Unreleased](#unreleased)
- [To 2.14.1](#to-2141)
- [To 2.14.0](#to-2140)
  - [CSRF action forms fail closed](#csrf-action-forms-fail-closed)
- [To 2.13.3](#to-2133)
- [To 2.13.2](#to-2132)
- [To 2.13.1](#to-2131)
- [To 2.13.0](#to-2130)
- [To 2.12.0](#to-2120)
- [To 2.11.0](#to-2110)
- [To 2.9.0](#to-290)
- [To 2.8.1](#to-281)
- [To 2.8.0](#to-280)
- [To 2.7.1](#to-271)
- [To 2.7.0](#to-270)
- [To 2.6.2](#to-262)
- [To 2.6.1](#to-261)
- [To 2.6.0](#to-260)
- [To 2.5.4](#to-254)
- [From 2.5.2 to 2.5.3](#from-252-to-253)
- [From 2.5.1 to 2.5.2](#from-251-to-252)
- [From 2.5.0 to 2.5.1](#from-250-to-251)
- [From 2.4.5 to 2.5.0](#from-245-to-250)
- [To 2.4.5](#to-245)
- [To 2.4.4](#to-244)
- [To 2.4.3](#to-243)
- [To 2.4.2](#to-242)
- [To 2.4.1](#to-241)
  - [SubmitType / ButtonType / ResetType and `help_attr`](#submittype--buttontype--resettype-and-help_attr)
- [To 2.4.0](#to-240)
  - [CSRF-only and GET filter helpers](#csrf-only-and-get-filter-helpers)
- [To 2.3.0](#to-230)
  - [Profile `defaults` scalars (`label` / `placeholder` / `help` / `required`)](#profile-defaults-scalars-label--placeholder--help--required)
- [To 2.1.0](#to-210)
  - [Twig Extra Bundle (REQ-TWIG-004)](#twig-extra-bundle-req-twig-004)
  - [Twig-CS-Fixer (maintainers)](#twig-cs-fixer-maintainers)
- [2.0.0 (2026-06-11)](#200-2026-06-11)
  - [Breaking: raised platform requirements](#breaking-raised-platform-requirements)
  - [Upgrade steps (from 1.x)](#upgrade-steps-from-1x)
- [2.0.x patch releases](#20x-patch-releases)
  - [2.0.20 (2026-08-04)](#2020-2026-08-04)
  - [2.0.19 (2026-07-29)](#2019-2026-07-29)
  - [2.0.18 (2026-07-29)](#2018-2026-07-29)
  - [2.0.17 (2026-07-26)](#2017-2026-07-26)
  - [2.0.16 (2026-07-22)](#2016-2026-07-22)
  - [2.0.15 (2026-07-20)](#2015-2026-07-20)
  - [2.0.14 (2026-07-20)](#2014-2026-07-20)
  - [2.0.13 (2026-07-20)](#2013-2026-07-20)
  - [2.0.12 (2026-07-20)](#2012-2026-07-20)
  - [2.0.11 (2026-07-18)](#2011-2026-07-18)
  - [2.0.10 (2026-07-18)](#2010-2026-07-18)
  - [2.0.9 (2026-07-18)](#209-2026-07-18)
  - [2.0.8 (2026-07-18)](#208-2026-07-18)
  - [2.0.7 (2026-07-09)](#207-2026-07-09)
  - [2.0.6 (2026-07-09)](#206-2026-07-09)
  - [2.0.5 (2026-07-03)](#205-2026-07-03)
  - [2.0.4 (2026-07-03)](#204-2026-07-03)
  - [2.0.3 (2026-07-03)](#203-2026-07-03)
  - [2.0.2 (2026-06-11)](#202-2026-06-11)
  - [2.0.1 (2026-06-11)](#201-2026-06-11)
- [1.x](#1x)
  - [1.0.0 (2025-03-03)](#100-2025-03-03)

## Unreleased

No action is needed for applications built on the Symfony FrameworkBundle and TwigBundle (every supported setup: `twig/extra-bundle`, a hard requirement, pulls both in).

- **Autoconfigured services (REQ-SF-003).** The bundle form types (`CsrfOnlyType`, `HiddenFieldsCsrfType`, `SearchQueryType`, `TranslatableMapType`), form type extensions (`InputGroupExtension`, `LocaleTabsExtension`, `CheckboxRowExtension`, `RequiredLabelSuffixExtension`, `HelpModalExtension`, `UnlabelledChoiceAriaLabelExtension`) and Twig extensions (`LocaleTabsTwigExtension`, `CsrfActionFormExtension`) no longer carry hand-written `form.type` / `form.type_extension` / `twig.extension` tags: they are `autoconfigure: true` and Symfony adds the same tags while compiling the container. Service ids, classes, arguments, options, form themes and Twig functions are unchanged.
  - Only if your application **decorates or re-registers** one of these services and **turns autoconfiguration off** for it, tag it yourself (`form.type`, `form.type_extension` or `twig.extension`).
  - Code that inspects the *uncompiled* `ContainerBuilder` (custom compiler passes running before `PassConfig::TYPE_BEFORE_OPTIMIZATION` priority 100, or tests calling `FormKitExtension::load()` alone) sees `isAutoconfigured() === true` instead of the tags; the tags appear after `ResolveInstanceofConditionalsPass`.
- **Demo only:** `make update-bundle` in `demo/symfony8` no longer passes `--with-all-dependencies` (it updates the bundle only, then dumps the autoloader, clears the cache and re-installs assets).

## To 2.14.1

From **2.14.0**:

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.14.0

From **2.13.3**:

```bash
composer update nowo-tech/form-kit-bundle
```

### CSRF action forms fail closed

`CsrfOnlyType`, `HiddenFieldsCsrfType`, `CsrfOnlyFormFactory` and `csrf_action_form()` now throw a `LogicException` ("requires Symfony form CSRF protection") when Symfony's form CSRF extension is not registered. No change is needed when the host has the standard setup (`symfony/security-csrf` installed, `framework.csrf_protection` and `framework.form.csrf_protection` enabled — the Flex defaults).

If the exception appears:

1. `composer require symfony/security-csrf` and enable `framework.csrf_protection: true` (and do not set `framework.form.csrf_protection: false`), including in the `test` environment of functional suites; or
2. for a form that is genuinely not state-changing, pass `'csrf_protection' => false` explicitly.

Previously these forms silently skipped CSRF validation in that configuration (`isValid()` returned `true` for a POST without a token).

## To 2.13.3

From **2.13.2**:

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.13.2

From **2.13.1**:

```bash
composer update nowo-tech/form-kit-bundle
```

Patch release. Run `php bin/console assets:install` for the new `locale-tabs.css`.

- Link `{{ asset('locale-tabs.css', 'nowo_form_kit') }}` with either renderer to style the locale tabs heading (title,
  help, label gap); its tab rules only target the neutral markup, so it is safe next to UiKit. Then drop host rules for
  `.nowo-form-kit-locale-tabs__title` / `__help` / `__heading`.
- With UiKit, update it to 2.5.3 (`composer update nowo-tech/ui-kit-bundle`) and drop host CSS that cancelled the
  `tabs` margin under the locale tab list.

## To 2.13.1

From **2.13.0**:

```bash
composer update nowo-tech/form-kit-bundle
```

Patch release. Notes:

- Options passed to `form_row(field, {locale_tabs: {...}})` now take effect (merged over the form-type option). If a
  template passed `locale_tabs` row variables that were silently ignored, check the result.
- `TranslatableMapType`: a `default_locale` outside `locales` now falls back to the configured default (when listed)
  or the first locale instead of being kept.
- With UiKit, update it to 2.5.2 (`composer update nowo-tech/ui-kit-bundle`) for hosts using polyfill-intl-icu
  without ext-intl.
- Entries mode: key the translation collection by locale (`indexBy: 'locale'`) so entity-level errors land inside
  their tab — see [USAGE](USAGE.md#locale-tabs-one-tab-per-language).

## To 2.13.0

From **2.12.0**:

```bash
composer update nowo-tech/form-kit-bundle
```

Additive (minor release). Notes:

- **Locale tabs with UiKit**: when nowo-tech/ui-kit-bundle 2.5+ is installed, fields with `locale_tabs` render through
  UiKit's `locale_tabs` partial (different markup: stacked fieldsets until `nowo-ui-tabs.js` upgrades them). Keep the
  2.12 markup with `nowo_form_kit.locale_tabs.renderer: native` or `'locale_tabs' => ['ui_kit' => false]`. The
  default-locale glyph is now `★` (`*` marks `required_locales`); `default_marker` restores `*`.
- **Form theme registered automatically**: `@NowoFormKitBundle/form/locale_tabs_theme.html.twig` is prepended to
  `twig.form_themes`. Drop it from your `twig.form_themes` (harmless if kept) or set
  `nowo_form_kit.locale_tabs.form_theme: false`.
- **Custom Twig environments** rendering `_locale_tabs.html.twig` need `Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension`
  (registered by the bundle in Symfony apps).
- **`checkbox_row`** (`static_blocks.html.twig`): non-Bootstrap profiles (`css_framework: tailwind`, root or the new
  profile-level key) no longer get `mb-3 form-check` / `form-check-input` / `form-check-label`; the row uses the
  profile's `defaults.row_attr.class`. Bootstrap hosts see no change.
- Optional: `profiles.<name>.help_attr` replaces `profiles.<name>.defaults.help_attr`.
- Optional: migrate per-bundle locale UIs — see [USAGE](USAGE.md#migrating-your-locale-ui).

## To 2.12.0

From **2.11.0** — opt-in combobox Stimulus peer

```bash
composer update nowo-tech/form-kit-bundle
```

- No breaking changes, no PHP / Twig / config change. The new `combobox` peer is a TypeScript source under `src/Resources/assets/stimulus-peers/`; nothing loads it unless you import it ([Usage — Combobox](USAGE.md#combobox-stimulus-peer-optional)).
- **Replacing an app-level combobox controller** (e.g. Beacon `assets/controllers/combobox_controller.ts`): replace the host file with a re-export of the kit peer (or register the kit class under `combobox`), then delete the host test. Targets and action names are the same, so templates and form types keep working. Differences to check:
  - The default required message is now `"Please select an option."`; `data-required-message` on the query still wins, or set `data-combobox-required-message-value` on the controller element.
  - The list stays closed after a selection (the host version reopened it on the focus that followed), and `Enter` on a closed list no longer auto-picks the first option.
  - The peer adds `role` / `aria-*` attributes and ids that are missing, toggles `is-active` on the keyboard-active option and closes on outside click; E2E selectors relying on the exact markup may need an update.

## To 2.11.0

From **2.10.0** — `csrf_action_form()` Twig function

```bash
composer update nowo-tech/form-kit-bundle
```

- No breaking changes. The bundle now registers the Twig function `csrf_action_form()` (on by default, `nowo_form_kit.twig.csrf_action_form`).
- **Apps with their own `csrf_action_form` Twig function** (e.g. a host `CsrfActionTwigExtension` copied from a boilerplate): nothing breaks — Twig keeps the extension registered last, and the bundle signature/behaviour is identical. To adopt, delete the host function (and its service if it only provided that function); templates stay unchanged. If you must keep the host version, set:

```yaml
nowo_form_kit:
    twig:
        csrf_action_form: false
```

From **2.9.0** — opt-in convention_requires_translation for help/placeholder keys

```bash
composer update nowo-tech/form-kit-bundle
```

- No breaking changes. No application upgrade steps.

## To 2.9.0

From **2.8.1** — explicit placeholders win over convention keys; ChoiceType placeholder semantics

```bash
composer update nowo-tech/form-kit-bundle
```

**Placeholder handling (FormOptionsMerger)** — behaviour fixes you may notice in rendered forms:

- An explicit `placeholder` (PHP options, profile `defaults.placeholder`, `field_types`, `by_form`) now replaces the `{form}.{field}.placeholder` convention key on text-like fields. If you relied on the convention key winning, remove the explicit value.
- `ChoiceType` fields (and `EntityType`, `EnumType`, `CountryType`, UX Autocomplete, custom subtypes) keep `placeholder` as the root empty-option label. `placeholder: false` now really removes the empty option — including FormKit's `addChoiceRadios()` / `addChoiceCheckboxes()` presets and `addChoiceWithFormPlaceholder(..., ['placeholder' => false])`, so non-required radio lists no longer show Symfony's "None" choice. Choice fields no longer carry `attr.placeholder` with the convention key, and profile / `by_form` `defaults.placeholder` is ignored for them (set the empty option in `field_types.choice`, `by_form.<form>.fields` or PHP).
- Workarounds that re-set the root `placeholder` after merging (e.g. `$merged['placeholder'] = …; unset($merged['attr']['placeholder'])`) keep working but are no longer needed.
- Checkbox, radio and hidden fields no longer receive `attr.placeholder` from `auto_placeholder`.
- `FormOptionsMerger::__construct()` gained two optional arguments (`?FormRegistryInterface $formRegistry`, `?FormTypeMap $formTypeMap`), wired automatically by the bundle.

## To 2.8.1

From **2.8.0** — money_scale 0 rounds half up; money and css_framework docs; vitest 4.

```bash
composer update nowo-tech/form-kit-bundle
```

- **Money fields with `money_scale: 0`:** input with decimals is now rounded half up (`"12.6"` → `13`, `"12.5"` → `13`) instead of truncated (`12`). Other scales are unchanged. If you relied on truncation, validate the input (e.g. a `Regex` constraint for whole numbers) or use your own transformer.

## To 2.8.0

From **2.7.1** — new accessibility extension, **enabled by default**; no breaking changes.

```bash
composer update nowo-tech/form-kit-bundle
php bin/console cache:clear
```

- **What changes in your HTML:** non-expanded `ChoiceType` fields (and subtypes such as `EntityType`) rendered with `label: false` and without `aria-label` / `aria-labelledby` now get `aria-label` from their translated `help`, else their translated `placeholder`. Labelled fields, expanded radios/checkboxes and fields that already have an accessible name are untouched. Update functional tests/snapshots that assert the exact `<select>` markup of label-less filters.
- **Opting out:**

  ```yaml
  nowo_form_kit:
      accessibility:
          unlabelled_choice_aria_label: false
  ```

- **Replacing an app-level equivalent** (e.g. Beacon's `App\Shared\Form\UnlabelledChoiceAriaLabelExtension`): delete the app class and its test. Keeping both is harmless (the second extension sees the `aria-label` set by the first and does nothing) but does the work twice.

## To 2.7.1

Bug fix only. If you enabled `stateless_csrf` on 2.7.0 and forms failed with *"The extended type … does not match any of the actual extended types"*, upgrade: no configuration change needed.

## To 2.7.0

From **2.6.2** — new opt-in features; no breaking changes.

```bash
composer update nowo-tech/form-kit-bundle
php bin/console assets:install   # only if you want locale-tabs.css
php bin/console cache:clear
```

- **No action required.** `LocaleTabsExtension` adds an unused-by-default `locale_tabs` option (default `false`) to every form type; `stateless_csrf` is disabled by default.
- **Replacing an app-level locale tabs partial** (e.g. `admin/_locale_field_tabs.html.twig`): include `@NowoFormKitBundle/form/_locale_tabs.html.twig` with the same variables (`field`, `default_child`, `tabs_id`, `testid`, `title`, `help`, `tabs_label`, `label`, `help_default`, `help_other`, `locales`, `default_locale`, `active_locale`). Differences: `locales` defaults to the field's children (pass `enabled_locales` explicitly); the screen-reader locale name comes from `locale_labels` instead of an app filter; styling uses `*_class` options / `locale-tabs.css` instead of hard-coded Tailwind classes. Stimulus `tabs` (`data-tab-id`) markup is unchanged, so an existing `tabs` controller keeps working. Use `include(..., with_context: false)` (or `with … only`) so outer `label`/`help` variables do not leak in.
- **Replacing an app-level stateless CSRF type extension** (e.g. `StatelessCsrfPublicFormTypeExtension`): move its `getExtendedTypes()` list to `nowo_form_kit.stateless_csrf.form_types`, set `enabled: true` (and `token_id` if it was not `submit`), then delete the app class. `stateless_token_ids` entries already in `framework.csrf_protection` can stay.

## To 2.6.2

From **2.6.1** — packaging fix.

```bash
composer update nowo-tech/form-kit-bundle
```

- No breaking changes. Composer now installs `symfony/asset` (`^7.4 || ^8.0`) with the bundle. Applications that previously crashed at boot with an asset configuration error (FrameworkBundle enabled, `symfony/asset` missing) work after the update; no configuration changes are needed.

## To 2.6.1

From **2.6.0** — dependency refresh.

```bash
composer update nowo-tech/form-kit-bundle
```

- No breaking changes. No application upgrade steps.

## To 2.6.0

From **2.5.4** — additive; no breaking changes.

Host forms extending `Nowo\FormKitBundle\Form\FormKitAbstractType` can drop local copies of these helpers:

- `twigOwnedChromeOptions()` — `label`/`help`/`placeholder`/`translation_domain` all `false`.
- `addChoiceWithFormPlaceholder(string $name, array $options)` — call inside `withBuilder()`; restores the ChoiceType empty option after FormKit moves `placeholder` to `attr` (`translation_domain` defaults to `form` when the empty option is kept).

If you already define methods with these names in a subclass, they keep overriding the kit versions (same signatures).

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.5.4

From **2.5.3** — REQ-CS-008 Igor FrankenPHP worker audit (igor-php require-dev, igor.json, make igor).

```bash
composer update nowo-tech/form-kit-bundle
php bin/console cache:clear
```

- No application upgrade steps for require-dev Igor tooling (REQ-CS-008). Consumers do not pull `igor-php/igor-php` transitively.

## From 2.5.2 to 2.5.3

From **2.5.2** — No application upgrade steps for normal usage.

FrankenPHP worker (kernel reused / `FRANKENPHP_RESET_KERNEL` unset or `0`):

- Keep calling `setFormKitFormName()` / `setFormKitConfigName()` / translations setters from the **constructor** (or pass names per `add*Type()` call). Do not set them inside an action with request-dependent values.
- Create `MultiStepWizardSession` via the factory **inside the action** (session is now resolved from `RequestStack` on each access).

See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

```bash
composer update nowo-tech/form-kit-bundle
```

## From 2.5.1 to 2.5.2

From **2.5.1** — No application upgrade steps. Hosts that register `@NowoFormKitBundle/form/static_blocks.html.twig` with Twig `strict_variables` no longer 500 when rendering Submit/Button/Reset widgets.

```bash
composer update nowo-tech/form-kit-bundle
```

## From 2.5.0 to 2.5.1

From **2.5.0** — No application upgrade steps. Demo-only fixes for `/nowo-special-fields` (Asset Mapper, widget assets, slide-to-confirm form theme, widget translation keys).

```bash
composer update nowo-tech/form-kit-bundle
```

## From 2.4.5 to 2.5.0

Optional nowo-tech widget helpers (`addOtp()`, `addPhone()`, `addPasswordToggle()`, `addPasswordStrength()`, `addIconSelector()`, `addCkeditor5Editor()`, `addTiptapEditor()`, `addTagInput()`, `addSlideToConfirm()` and `*Field` / `*FieldType` variants). Install only the packages you need (`composer suggest`). No host change required; Symfony `addPassword()` and FOS `addCKEditorField()` are unchanged.

Notes when using the new helpers:

- **`addTagInput()`** — do not pass `placeholder => false` (`TagType` expects a string).
- **`addSlideToConfirm()`** — the type defaults to `mapped: false`; set `mapped => true` when the value must land on the model. It has no `placeholder` option.
- **`addPasswordToggle()` / `addCkeditor5Editor()`** — distinct from Symfony `addPassword()` and FOS `addCKEditorField()` (CKEditor 4).

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.5

From **2.4.4** — No application upgrade steps.

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.4

From **2.4.3** — No application upgrade steps.

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.3

From **2.4.2** — No application upgrade steps. Host apps that already define `nowo_form_kit.profiles.filter` are unchanged; others gain the built-in filter profile automatically.

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.2

From **2.4.1** — No application upgrade steps. **Demos only:** Hot Reload Bundle `^1.4` (FrankenPHP Mercure/`hot_reload`, `dev`/`test`).

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.1

### SubmitType / ButtonType / ResetType and `help_attr`

No host change required. Upgrade if CI or runtime fails with `The option "help_attr" does not exist` on `SubmitType` after FormKit ≥ 2.3.0 (profile defaults always included `help_attr`).

```bash
composer update nowo-tech/form-kit-bundle
```

## To 2.4.0

### CSRF-only and GET filter helpers

No breaking changes. Optional new APIs for hosts that previously duplicated CSRF-only / GET filter form helpers:

```bash
composer update nowo-tech/form-kit-bundle
php bin/console cache:clear
```

- Prefer `Nowo\FormKitBundle\Form\CsrfOnlyFormFactory` over app-local CSRF-only factories.
- Prefer `Nowo\FormKitBundle\Form\AbstractGetFilterType` + a `filter` profile for GET list filters.
- See [CSRF.md](CSRF.md).

If you already map `search` in `nowo_form_kit.type_map`, you can drop the host override — Form Kit now ships it built-in.

## To 2.3.0

### Profile `defaults` scalars (`label` / `placeholder` / `help` / `required`)

Optional keys under `profiles.<name>.defaults` (and `by_form.<form>.defaults`). No host change required unless you want to collapse repeated `field_types.*.label: false` (or set a profile-wide `required: false`).

```yaml
nowo_form_kit:
    profiles:
        filter:
            defaults:
                label: false
                required: false
```

`field_types` / PHP options still override. `placeholder: false` / `help: false` in defaults also suppress the auto convention keys.

```bash
composer update nowo-tech/form-kit-bundle
php bin/console cache:clear
```

## To 2.1.0

From **2.0.20** — Adds required Twig Extra (REQ-TWIG-004) and Twig-CS-Fixer. Register TwigExtraBundle if Flex did not.

```bash
composer update nowo-tech/form-kit-bundle
php bin/console cache:clear
```

### Twig Extra Bundle (REQ-TWIG-004)

Hosts that render this bundle's Twig templates must install:

```bash
composer require twig/extra-bundle twig/string-extra
```

and enable `Twig\Extra\TwigExtraBundle\TwigExtraBundle`. Flex recipes usually register it automatically.

### Twig-CS-Fixer (maintainers)

Package maintainers: `composer twig:lint` / `composer twig:fix` use `.twig-cs-fixer.php` over `src/` (and `templates/` when present).

## 2.0.0 (2026-06-11)

### Breaking: raised platform requirements

- **PHP** minimum is now **8.2** (was 8.1).
- **Symfony** minimum is now **^7.4 || ^8.0** (7.4, 8.0, 8.1). Symfony 6.x and Symfony 7.0–7.3 are no longer supported.
- **Symfony 8** requires **PHP 8.4+** (Symfony platform requirement).

If your project runs on PHP 8.1 or Symfony 6.4 / 7.0–7.3, stay on **form-kit-bundle 1.x** (`^1.0`).

### Upgrade steps (from 1.x)

1. **Upgrade your platform** before bumping the bundle:
   - PHP **8.2+** (Symfony 7.4), or PHP **8.4+** (Symfony 8.0 / 8.1).
   - Symfony **7.4** or **8.x**.
2. Update the package constraint:

   ```bash
   composer require nowo-tech/form-kit-bundle:^2.0
   ```

3. **Optional integrations** — `a2lix/translation-form-bundle` is no longer a hard dependency; install it only if you use the `translations` type (`^3.2` on PHP 8.2 / Symfony 7.4, `^4.0` on PHP 8.4+ / Symfony 7.4|8).
4. **Help modal** — if you adopt `help_modal`, run `php bin/console assets:install public` and load assets with the named package: `asset('help-modal.js', 'nowo_form_kit')` (optional CSS: `asset('help-modal.css', 'nowo_form_kit')`). Hard-coded `/bundles/nowoformkit/...` paths are obsolete; see [Usage — Help modal](USAGE.md#help-modal-optional).
5. Review [CHANGELOG](CHANGELOG.md) for new helpers (choice presets, model transformers, **FormKitControllerTrait**) and demo pages as reference.

No configuration key renames are required for existing YAML; public services and extension points remain compatible where the platform allows installation.

## 2.0.x patch releases

### 2.0.20 (2026-08-04)

- **Dev dependencies / CI** — PHP CS Fixer, Rector, PHPStan, phpstan-frankenphp, and `actions/stale` bumps. No Composer package API change for **form-kit-bundle** consumers.
- **Demos only** — Symfony 8 demo pulls Nowo special-field bundles from Packagist; the `/var/nowo-bundles` Docker volume is no longer required.

### 2.0.19 (2026-07-29)

- **Repository / Make only** — Compose V2 detection and optional monorepo Makefile includes for CI. No Composer package API or config change for integrators.

### 2.0.18 (2026-07-29)

- **Repository / QA** — Empty PHPStan baseline ([PHPSTAN.md](PHPSTAN.md)); `make coverage-check` / `check-open-prs` / `demo-smoke`; CI fails on direct Symfony deprecations from this package. No YAML or public API rename for integrators.
- **Constraint FQCNs** — Invalid constraint class names (not extending Symfony `Constraint`) now throw `InvalidArgumentException` from `ConstraintDefinitionFactory`.

### 2.0.17 (2026-07-26)

**Help modal asset package (REQ-ASSETS-004):** the bundle registers Symfony asset package **`nowo_form_kit`** (`base_path: /bundles/nowoformkit`) via `FormKitExtension::prepend()`.

Replace hard-coded paths:

```twig
{# Before #}
<script defer src="{{ asset('bundles/nowoformkit/help-modal.js') }}"></script>

{# After #}
<script defer src="{{ asset('help-modal.js', 'nowo_form_kit') }}"></script>
<link rel="stylesheet" href="{{ asset('help-modal.css', 'nowo_form_kit') }}">
```

Run `php bin/console assets:install` after upgrade. Do not duplicate `framework.assets.packages.nowo_form_kit` in the application config unless you intentionally override CDN/base URL.

**Help modal JS:** rebuilt `help-modal.js` portals help modals to `document.body` and watches the DOM for forms loaded later (Turbo / Live / AJAX). No Twig API change.

**Demos:** Symfony 8 image is PHP **8.5**; switch FrankenPHP classic vs worker with `FRANKENPHP_MODE` in `.env` (recreate container; no rebuild).

### 2.0.16 (2026-07-22)

- **Twig namespace** — Update logical template names from `@NowoFormKit/...` to `@NowoFormKitBundle/...` (e.g. form theme, help-modal shells, `form_renderer`).
- **Application overrides** — Move overrides to `templates/bundles/NowoFormKitBundle/...` (was `templates/bundles/NowoFormKit/...`).
- Clear Twig cache after upgrading: `php bin/console cache:clear`.

### 2.0.15 (2026-07-20)

- **Repository / CI only** — Enforces no Cursor co-author trailers in git history ([GITHUB_CI.md](GITHUB_CI.md)); contributors should run `make setup-hooks` once per clone. No Composer package API or runtime change for integrators.

### 2.0.14 (2026-07-20)

- **Repository only** — Adds [Contributor Covenant Code of Conduct](../CODE_OF_CONDUCT.md). No Composer package API or runtime change for integrators.

### 2.0.13 (2026-07-20)

- **Demos only** — Symfony 8 demo lockfile / `config/reference.php` sync and choice-fields copy cleanup after removal of the Symfony 7 demo. No Composer package API or runtime change for **form-kit-bundle** consumers.

### 2.0.12 (2026-07-20)

- **Dev dependencies only** — PHP CS Fixer and Rector bumps in `composer.lock`. No Composer package API, config, or runtime change for integrators.

### 2.0.11 (2026-07-18)

**YAML rename (BC preserved):** prefer `default_profile` / `profiles` over `default_config` / `configs`. Legacy keys still work via `beforeNormalization`. Prefer parameters `nowo_form_kit.default_profile` / `nowo_form_kit.profiles` (legacy `nowo_form_kit.default_config` / `nowo_form_kit.configs` remain set to the same values).

PHP APIs keep the word “config” for BC: `#[FormKitConfig('…')]`, `setFormKitConfigName()`, etc. — they still select a **profile** name.

```yaml
# Before
nowo_form_kit:
    default_config: default
    configs:
        default:
            alias: default
            # ...

# After
nowo_form_kit:
    default_profile: default
    profiles:
        default:
            alias: default
            # ...
```

**Docs:** usage entry points are called **strategies** — **Options**, **Kit**, **Controller**, **Wrapped** — with technique IDs such as **bound-builder** and **array-build**. See [USAGE — strategies](USAGE.md#usage-strategies).

### 2.0.10 (2026-07-18)

Additive / opt-in (no BC break for existing YAML without the new keys):

- **`by_form`** — Optional per-form defaults under each config (or legacy root when `configs` is empty).
- **`constraint_message_convention`** — Defaults to `false`; enable to auto-fill constraint message translation keys.
- **FormTypeMap** — More built-in snake_case names (date, money, collection, …); existing names unchanged.
- **`#[FormKitConfig]`** — Optional attribute on form types; `setFormKitConfigName()` remains supported and wins when called.
- **Optional helpers** — `addDropzone*` / `addCropper*` (require UX packages; throw if missing).
- **Flex recipe stub 2.0** — see [RECIPE.md](RECIPE.md); submit to recipes-contrib when ready.
- **Demo** — `/conditional-fields-live` with Symfony UX Live Component.

### 2.0.9 (2026-07-18)

Repository-only (demos / Makefiles / docs; no Composer package or bundle API change):

- **demo/symfony7** removed — Use **demo/symfony8**. `make update-deps` and demo Makefiles target **symfony8** only. Symfony **7.4+** remains supported at runtime.
- **Demo `/nowo-special-fields`** — Password examples cover toggle only, strength only, and combined toggle + strength.

### 2.0.8 (2026-07-18)

- **Additive API (no BC break)** — Prefer `withBuilder($builder, fn () => …)` with `addTextField()` / `addEmailField()` / choice and transformer `*Field` helpers. Existing `addText($builder, …)` and peers remain supported.
- **`resolveFieldOptions()`** — Use when adding fields from `FormEvents` (`PRE_SET_DATA` / `PRE_SUBMIT`) via `$form->add(...)`.
- **Conditional fields** — Still no built-in `when` option; use Symfony events or build-time `if` (see [USAGE — Conditional fields](USAGE.md#conditional-fields-show-one-field-or-another)). Demos: `/conditional-fields`, `/kit-api-patterns`.
- **No config key changes** — Optional: use a second named profile with `setFormKitConfigName('…')` (demo enables `configs.bootstrap`).

### 2.0.7 (2026-07-09)

- **Demos only** — Symfony 7 demo lockfile realigned to **7.4.x**; both demos’ `composer.lock` and `config/reference.php` refreshed. No Composer package API or config change for **form-kit-bundle** consumers.

### 2.0.6 (2026-07-09)

- **Repository only** — GitHub Spec Kit baseline ([`specs/001-baseline/`](../specs/001-baseline/)), [`.specify/`](../.specify/), Cursor skills (`.cursor/skills/speckit-*`), and [`docs/SPEC-KIT.md`](SPEC-KIT.md). No Composer package API, config key, or runtime behavior change for integrators.
- **Maintainers** — When changing production code under `src/`, update the baseline spec and code inventory per [`SPEC-KIT.md`](SPEC-KIT.md).

### 2.0.5 (2026-07-03)

- **`static_blocks` form theme** — If you register `@NowoFormKitBundle/form/static_blocks.html.twig` together with **Bootstrap 5**, list **static_blocks first** (lowest priority), then `bootstrap_5_layout.html.twig`, then other bundle themes. See [Usage — Custom static blocks](USAGE.md#custom-static-blocks-in-the-form-hr-alert).
- **Expanded choices** — `addChoiceRadios()` / `addChoiceCheckboxes()` no longer apply global `form-control` to the choice container (upgrade is automatic when using the trait helpers).
- **Demos only** — Choice / Nowo special-fields pages: phone-input form theme, password-toggle CSS, icon-selector theme workaround, and Makefile Docker fixes (no Composer package API change).

### 2.0.4 (2026-07-03)

- **ConstraintDefinitionFactory** — Validator constraints from YAML/config use named constructor arguments (fixes CI on Symfony 7.4 / 8.x where array options are rejected).

### 2.0.3 (2026-07-03)

- **Demos** — New **Nowo special fields** page (OTP, phone, password widgets, icon selector, Tiptap, CKEditor 5) in symfony7/symfony8; requires sibling `bundles/` mounted at `/var/nowo-bundles` when using Docker path repos (see [demo/README](../demo/README.md)).
- **demo/symfony7** — PHP **8.2** image, **a2lix ^3.2**, A2lix config key `locales` (not `enabled_locales`).
- **demo/symfony8** — Composer requires **PHP >= 8.4**.
- **Tests / docs** — PHP coverage **99.59%**; README badges and TOC updates; no bundle API or config key changes for consumers.

### 2.0.2 (2026-06-11)

Repository-only (demos / Makefiles; no Composer package or bundle API change):

- **Web Profiler (symfony7 demo)** — Dev routes updated for Symfony 7.4+ (`*.php` instead of `*.xml`).
- **`make up` / `make update-deps`** — Fixed Makefile `include` syntax in demo Makefiles; `make update-deps` from the bundle root updates dependencies and **starts** symfony7/symfony8 demos (shared `.scripts` Makefile).
- **Importmap** — Demo entrypoints run `importmap:install` so Stimulus / Asset Mapper vendors are installed after `composer update` (avoids HTTP 500 on `/en/`).

### 2.0.1 (2026-06-11)

Repository-only: **demo/symfony6** removed. `make update-deps` and demo Makefiles target **symfony7** / **symfony8** only. No change to the published Composer package or bundle API.

## 1.x

### 1.0.0 (2025-03-03)

First stable release. No upgrade steps required.

- **Requirements:** PHP >= 8.1, Symfony ^6.4 || ^7.0 || ^8.0.
- **Optional:** If you use the `translations` type (A2lix TranslationFormBundle), constraint is `^3.2 || ^4.0` (use 3.x on PHP 8.2, 4.x on PHP 8.4+).
