# Upgrading

This document describes how to upgrade between major versions of Form Kit Bundle.

## Table of contents


- [From 2.4.5 to 2.5.0](#from-245-to-250)
- [Unreleased](#unreleased)
- [To 2.4.5](#to-245)
- [To 2.4.4](#to-244)
- [To 2.4.3](#to-243)
- [To 2.4.2](#to-242)
- [To 2.4.1](#to-241)
- [To 2.4.0](#to-240)
- [To 2.3.0](#to-230)
- [To 2.1.0](#to-210)
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
