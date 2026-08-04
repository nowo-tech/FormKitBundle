# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[2.2.0] - 2026-08-05](#220-2026-08-05)
- [[2.1.0] - 2026-08-04](#210-2026-08-04)
- [[2.0.20] - 2026-08-04](#2020-2026-08-04)
  - [Changed](#changed)
- [[2.0.19] - 2026-07-29](#2019-2026-07-29)
  - [Changed](#changed)
- [[2.0.18] - 2026-07-29](#2018-2026-07-29)
  - [Added](#added)
  - [Changed](#changed)
  - [Fixed](#fixed)
- [[2.0.17] - 2026-07-26](#2017-2026-07-26)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.16] - 2026-07-22](#2016-2026-07-22)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.15] - 2026-07-20](#2015-2026-07-20)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.14] - 2026-07-20](#2014-2026-07-20)
  - [Added](#added)
- [[2.0.13] - 2026-07-20](#2013-2026-07-20)
  - [Changed](#changed)
- [[2.0.12] - 2026-07-20](#2012-2026-07-20)
  - [Changed](#changed)
- [[2.0.11] - 2026-07-18](#2011-2026-07-18)
  - [Changed](#changed)
- [[2.0.10] - 2026-07-18](#2010-2026-07-18)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.9] - 2026-07-18](#209-2026-07-18)
  - [Removed](#removed)
  - [Changed](#changed)
- [[2.0.8] - 2026-07-18](#208-2026-07-18)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.7] - 2026-07-09](#207-2026-07-09)
  - [Fixed](#fixed)
  - [Changed](#changed)
- [[2.0.6] - 2026-07-09](#206-2026-07-09)
  - [Added](#added)
  - [Changed](#changed)
- [[2.0.5] - 2026-07-03](#205-2026-07-03)
  - [Fixed](#fixed)
  - [Changed](#changed)
  - [Documentation](#documentation)
- [[2.0.4] - 2026-07-03](#204-2026-07-03)
  - [Fixed](#fixed)
- [[2.0.3] - 2026-07-03](#203-2026-07-03)
  - [Added](#added)
  - [Changed](#changed)
  - [Fixed](#fixed)
  - [Documentation](#documentation)
- [[2.0.2] - 2026-06-11](#202-2026-06-11)
  - [Fixed](#fixed)
  - [Changed](#changed)
  - [Documentation](#documentation)
- [[2.0.1] - 2026-06-11](#201-2026-06-11)
  - [Removed](#removed)
  - [Changed](#changed)
  - [Documentation](#documentation)
- [[2.0.0] - 2026-06-11](#200-2026-06-11)
  - [Added](#added)
  - [Changed](#changed)
  - [Documentation](#documentation)
- [[1.0.0] - 2025-03-03](#100-2025-03-03)
  - [Added](#added)
  - [Changed](#changed)

## [Unreleased]

## [2.2.0] - 2026-08-05

### Added

- Profile options **`auto_placeholder`** and **`auto_help`** (default `true`): when `false`, FormOptionsMerger does not inject `{form}.{field}.placeholder` / `.help` convention keys (kits can set labels only without empty help keys in the UI).
- **`FormOptionsTrait::addWithDefaults()`** inherits the form-level `translation_domain` into field options when the field does not set one, so AuthKit/host form domains win over the profile default.

[2.2.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.2.0

## [2.1.0] - 2026-08-04

### Changed

- **PHPStan:** analyse `src/` only (align with UiKit); exclude unused `FormKitControllerTrait` from the scan path. Clear remaining `class-string` / `list` typing for Form builder helpers.
- **PHPStan stubs:** `stubs/OptionalFormTypes.php` for optional UX / A2lix / CKEditor form type FQCNs (still Composer `suggest` only at runtime).
- Demo `test-coverage` aliases HTTP smoke (`make verify`) — the demo has no PHPUnit suite; healthchecks accept HTTP **2xx/3xx**.

### Added
- **REQ-TWIG-004:** require `twig/extra-bundle` + `twig/string-extra`; `make check-twig-extra` in `release-check`; demos register `TwigExtraBundle`.
- **Twig-CS-Fixer:** `vincentlanglet/twig-cs-fixer`, `.twig-cs-fixer.php`, `composer twig:lint` / `twig:fix`.

[2.1.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.1.0

## [2.0.20] - 2026-08-04

### Changed

- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.17 → 3.95.18**; `rector/rector` **2.5.8 → 2.5.9**; `phpstan/phpstan` **2.2.6 → 2.2.7**; `nowo-tech/phpstan-frankenphp` **1.0.1 → 1.0.2**; GitHub Action `actions/stale` **10 → 11** (Dependabot).
- **demo/symfony8** — Nowo special-field widgets install from **Packagist** (`^1.0` / `^2.0`) instead of path repos under `/var/nowo-bundles`; removed that Compose volume; `prefer-stable: true`; refreshed `composer.lock` and `config/reference.php`.
- **demo/README** — Documents Packagist-based Nowo widget packages (no sibling-path mount required).

## [2.0.19] - 2026-07-29

### Changed

- **Makefiles (REQ-MAKE-009 / REQ-MAKE-010)** — Prefer Docker Compose V2 (`docker compose`) with V1 fallback; optional `-include` of monorepo `update-deps` helpers so standalone CI checkouts do not fail when `../.scripts/` is absent.
- **PHP CS Fixer** — Import/`use` cleanups after 2.0.18 (traits, transformers, tests); no behaviour change.

## [2.0.18] - 2026-07-29

### Added

- **`docs/PHPSTAN.md`** — PHPStan level 8 policy: empty baseline (`ignoreErrors: []`); fix findings in code/tests instead of silencing.
- **QA Make targets** — `coverage-check` (fail if Lines &lt; 99%, REQ-TEST-003), `check-open-prs` (REQ-REL-003), `demo-smoke` (REQ-TEST-011); wired into `release-check`.
- **Scripts** — `.scripts/coverage-fail-under.sh`, `.scripts/check-open-prs.sh`.
- **PHPUnit / CI** — `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` (REQ-SF-005) in `phpunit.xml.dist` and CI test jobs.
- **PHPUnit** — Broader coverage for traits, controller trait, constraint factory, TwigPathsPass, FormKitExtension.

### Changed

- **PHPStan** — Cleared `phpstan-baseline.neon` suppressions; PHPDoc and small type-safe cleanups across `src/` (traits, mergers, CSS utilities, constraints, static types).
- **Docs** — Table of contents on CHANGELOG, UPGRADING, INSTALLATION, CONFIGURATION, ROADMAP, SECURITY notes, GITHUB_CI, SPEC-DRIVEN-DEVELOPMENT; README coverage badge **99.4%**.
- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.15 → 3.95.17**; `rector/rector` **2.5.7 → 2.5.8**; `phpstan/phpstan` **2.2.5 → 2.2.6** (Dependabot).
- **Flex recipe stubs** — Comment URLs point to GitHub repo **FormKitBundle**.

### Fixed

- **`.github/SECURITY.md`** — Correct product name (Form Kit Bundle).
- **`ConstraintDefinitionFactory`** — Reject FQCNs that do not extend Symfony `Constraint`.

## [2.0.17] - 2026-07-26

### Added

- **REQ-ASSETS-004** — `FormKitExtension` implements `PrependExtensionInterface` and registers the `nowo_form_kit` asset package (`base_path: /bundles/nowoformkit`). Load help-modal assets with `asset('help-modal.js', 'nowo_form_kit')` / `asset('help-modal.css', 'nowo_form_kit')`.
- **REQ-DOCS-017** — FrankenPHP Friendly Worker Mode banner in README (`docs/images/frankenphp-friendly.png`).
- **REQ-CS-005** — `nowo-tech/phpstan-frankenphp` in `require-dev` with `ruleset-classic` + `ruleset-worker` in `phpstan.neon.dist`.
- **Help modal portal** — `help-modal.ts` MutationObserver keeps Form Kit help modals on `document.body` for dynamic/background forms; stale duplicates by id are removed on refresh.

### Changed

- **REQ-DEMO-010** — Symfony 8 demo uses `dunglas/frankenphp:1-php8.5-alpine`; `FRANKENPHP_MODE` (`classic` \| `worker`, default `worker`) in `.env.example` / Compose / entrypoint.
- **Docs / demo / recipe** — Help modal examples use the named asset package (no hard-coded `/bundles/nowoformkit/...` paths).
- **PHP CS Fixer** — `fully_qualified_strict_types.import_symbols` enabled; related `use` import cleanups in src/tests.

## [2.0.16] - 2026-07-22

### Added

- **`TwigPathsPass`** — Registers the Twig namespace **`NowoFormKitBundle`** and prepends `templates/bundles/NowoFormKitBundle/` so application overrides win.
- **PHPUnit** — Coverage for `TwigPathsPass` and bundle `build()` registration.

### Changed

- **Twig logical names** — Prefer `@NowoFormKitBundle/...` (was `@NowoFormKit/...`) for form themes, help-modal shells, and `form_renderer`. Docs, Flex recipe stub, demo, and Spec Kit baseline updated. See [UPGRADING](UPGRADING.md).
- **composer.lock / demo lock** — Dependency refresh (PHPUnit, related packages).

## [2.0.15] - 2026-07-20

### Added

- **REQ-GIT-001** — No Cursor `Co-authored-by` trailers: `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh` / `strip-cursor-coauthor-from-history.sh`, Cursor rule, CI job **Git history (no Cursor co-author)**, Make targets `setup-hooks` / `check-no-cursor-coauthor` / `strip-cursor-coauthor-from-history`, and [docs/GITHUB_CI.md](GITHUB_CI.md).
- **PHPUnit stubs** — `DummyFormTypeWithTwoParams` / `DummyFormTypeWithWrongParamType` cover compiler-pass skips for invalid `setFormOptionsMerger` signatures.
- **CSS utility tests** — Empty class-token cases for Bootstrap and Foundation `orderClasses()`.

### Changed

- **CONTRIBUTING / RELEASE / README** — Document git hooks and link to GitHub CI requirements.
- **`.gitignore`** — Ignore `.cursor/sandbox.json`.

## [2.0.14] - 2026-07-20

### Added

- **`CODE_OF_CONDUCT.md`** — Contributor Covenant 2.1; linked from README and [Contributing](CONTRIBUTING.md).

## [2.0.13] - 2026-07-20

### Changed

- **demo/symfony8** — Refreshed `composer.lock` (Guzzle PSR-7, Select All Choice, Twig Inspector, OTP path package branch) and regenerated `config/reference.php` for `default_profile` / `profiles`, `by_form`, and `constraint_message_convention`.
- **demo translations (EN/ES/FR/DE)** — Choice-fields copy no longer mentions removed Symfony 7 demo (“this demo” only).
- **demo/Makefile** — Comment aligned with symfony8-only targets.

## [2.0.12] - 2026-07-20

### Changed

- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.11 → 3.95.15**; `rector/rector` **2.5.3 → 2.5.7** (Dependabot; no runtime API change).

## [2.0.11] - 2026-07-18

### Changed

- **Config naming (AuditKit-style)** — `default_config` / `configs` renamed to `default_profile` / `profiles`. Legacy YAML keys and container parameters (`nowo_form_kit.default_config`, `nowo_form_kit.configs`) remain accepted during transition. See [UPGRADING](UPGRADING.md).
- **Docs — usage strategies** — Named strategies (**Options**, **Kit**, **Controller**, **Wrapped**) and techniques (**bound-builder**, **array-build**, **named-config**, **direct-merge**, **resolve-field-options**) in [USAGE](USAGE.md#usage-strategies); README / ROADMAP / INSTALLATION / recipes / demo YAML aligned with `profiles`.

## [2.0.10] - 2026-07-18

### Added

- **`by_form` config** — Per-form `defaults` / `fields` keyed by form name (block prefix), merged after `field_types` and before PHP options.
- **`constraint_message_convention`** — Opt-in: constraints without an explicit message get `{form}.{field}.constraints.{Name}` (and Length-style `.min` / `.max` message keys).
- **FormTypeMap built-ins** — Extended snake_case map: `date`, `datetime`, `money`, `collection`, `tel`, `file`, `submit`, and other core Symfony form types.
- **`#[FormKitConfig('name')]`** — PHP attribute on form types (`FormOptionsTrait` / `FormKitTrait`) to select a named config; `setFormKitConfigName()` still overrides.
- **Optional helpers** — `addDropzone()` / `addDropzoneField()` / `addDropzoneFieldType()` (`symfony/ux-dropzone`) and `addCropper()` / `addCropperField()` / `addCropperFieldType()` (`symfony/ux-cropperjs`) on FormOptionsTrait, FormKitTrait, and FormKitControllerTrait (`class_exists` guards).
- **Flex recipe stub 2.0** — [`.symfony/recipe/nowo-tech/form-kit-bundle/2.0/`](../.symfony/recipe/nowo-tech/form-kit-bundle/2.0/) with `nowo_form_kit.yaml`, Twig `static_blocks` form theme, and [docs/RECIPE.md](RECIPE.md) for recipes-contrib submission.
- **Demo Live Component** — `/{locale}/conditional-fields-live` (`ConditionalFieldsLive` + `ComponentWithFormTrait`) using the same FormEvents conditional form.
- **Spec Kit** — Baseline updated for `by_form`, constraint message convention, and `#[FormKitConfig]` (`US-02b` / `US-07`, `FR-FORM-011`).

### Changed

- **ROADMAP / INSTALLATION / USAGE / CONFIGURATION / README** — Aligned with symfony8-only demo, `by_form` / constraint convention, expanded type map, Dropzone/Cropper helpers, recipe docs, Live demo, layout examples (grid / floating labels), and `#[FormKitConfig]`.

## [2.0.9] - 2026-07-18

### Removed

- **demo/symfony7** — Symfony 7 demo removed from the repository. Use **demo/symfony8** (PHP 8.4+). Does not affect the Composer package (`demo/` is excluded from the archive). Bundle runtime still supports Symfony **7.4+**.

### Changed

- **Demo `/nowo-special-fields`** — Password examples: toggle only, strength only (`use_password_toggle => false`), and **combined** toggle + strength (`PasswordStrengthType` + `use_password_toggle => true`).
- **Make / docs** — Root and demo Makefiles, README, and DEMO-FRANKENPHP target **symfony8** only.

## [2.0.8] - 2026-07-18

### Added

- **`withBuilder()` + `add*Field()`** on `FormOptionsTrait` and `FormKitTrait` — bind the form builder once and add fields without repeating `$builder` (e.g. `$this->addTextField('name')`). Also: `boundBuilder()`, `addTypedField()` / `addNamedField()`, `buildFieldsFromArray()`, and bound aliases for choice presets and model-transformer helpers. Existing `addText($builder, …)` APIs remain unchanged.
- **`resolveFieldOptions()`** on `FormOptionsTrait` — merge conventions/config without adding the field (for `FormEvents` + `$form->add()`).
- **Docs + demo: conditional fields** — [USAGE](USAGE.md) patterns (build-time `if`, `PRE_SET_DATA` / `PRE_SUBMIT`, UI hide, Live Component) and demo route `/conditional-fields` (`ConditionalFieldsDemoType` + `BuildTimeConditionalDemoType`).
- **Demo coverage** — `/kit-api-patterns` (`FormKitAbstractType` snake_case + named `bootstrap` config); expanded `/controller-form` (select, radios, money); `boundBuilder()` on autocomplete demo; `configs.bootstrap` enabled in demo YAML.
- **Spec Kit baseline** — [`specs/001-baseline/`](../specs/001-baseline/) updated for bound-builder helpers, `resolveFieldOptions`, and conditional-field patterns (`US-03b`/`US-03c`/`US-06`, `FR-FORM-009`/`FR-FORM-010`, `SC-004`).

### Changed

- **README / INSTALLATION / USAGE** — Prefer `withBuilder()` + `add*Field()` in quick-start examples; document conditional-field patterns and new demo routes.

## [2.0.7] - 2026-07-09

### Fixed

- **demo/symfony7** — `composer.lock` resolved Symfony components to **7.4.x** again (lock had drifted to **8.2.x-dev** branches incompatible with the Symfony 7 demo platform).

### Changed

- **Demos (symfony7 / symfony8)** — Refreshed `composer.lock` (Guzzle, Intervention Image, Nowo path bundles, Symfony patches) and regenerated `config/reference.php` without `declare(strict_types=1)` to match Symfony Flex auto-generated config reference files.

## [2.0.6] - 2026-07-09

### Added

- **GitHub Spec Kit** — Baseline spec workflow for maintainers: [`.specify/`](../.specify/), Cursor Agent skills (`.cursor/skills/speckit-*`), and [`specs/001-baseline/`](../specs/001-baseline/) (`spec.md`, `code-inventory.md` covering **100%** of production code under `src/`).
- **`docs/SPEC-KIT.md`** — Operator manual: Specify CLI install, `specify init`, folder layout, Cursor `/speckit-*` skills, and maintainer checklist.

### Changed

- **`docs/SPEC-DRIVEN-DEVELOPMENT.md`** — Three-layer model (Spec Kit baseline, product behavior, `REQ-*` anchors); user stories aligned with Form Kit integrator goals; workflow step to keep Spec Kit artifacts in sync when `src/` changes.
- **README** — Link to **GitHub Spec Kit** documentation.
- **demo/symfony8** — `config/reference.php` synced via CI PHP CS Fixer.

## [2.0.5] - 2026-07-03

### Fixed

- **`static_blocks.html.twig`** — Use Twig `{% use %}` instead of `{% extends form_div_layout %}` so only bundle-defined blocks are registered. When this theme was listed after Bootstrap 5, inherited `form_div` radio/checkbox widgets overrode Bootstrap 5 and broke expanded choice fields (missing labels / `form-check` markup).
- **`FormOptionsTrait`** — `addChoiceRadios()` and `addChoiceCheckboxes()` disable `placeholder` and clear default `attr.class` (`form-control`) on the widget root so expanded choices and checkbox groups render correctly with Bootstrap 5.

### Changed

- **Demos (symfony7 / symfony8)** — **Choice fields** and **Nowo special fields** layout: `twig.form_themes` order (`static_blocks` and `@NowoPhoneInputBundle/Form/phone_input_widget.html.twig` before Bootstrap 5), `field_types` for `checkbox` / `choice`, demo CSS (OTP horizontal layout, password-strength markers), password-toggle CSS, icon-selector form theme workaround, and widget translations (password-strength labels, editor placeholders).
- **demo Makefiles** — Consistent `DOCKER_COMPOSE` / `SHELL` usage (fixes `docker: Permission denied` when `COMPOSE` env collides with the Make variable).

### Documentation

- **USAGE** — Form theme ordering with Bootstrap 5 and expanded choice fields.
- **UPGRADING** — Notes for 2.0.5.
- **demo/README** — Twig form themes and Nowo widget assets for special-fields demo.

## [2.0.4] - 2026-07-03

### Fixed

- **ConstraintDefinitionFactory** — Instantiate Symfony Validator constraints with named arguments (`new $class(...$options)`) instead of a single options array, compatible with Symfony Validator 7.4+ / 8.x (`NotBlank`, `Length`, etc.).
- **tests/Stubs** — PSR-4 autoload for compiler-pass dummy form types (fixes `FormOptionsMergerInjectorCompilerPassTest` after `composer dump-autoload -o`).

## [2.0.3] - 2026-07-03

### Added

- **Demos (symfony7 / symfony8)** — **Nowo special fields** page (`/{locale}/nowo-special-fields`): OTP, phone, password toggle/strength, icon selector, Tiptap, and CKEditor 5 widgets from sibling **nowo-tech** path repos, integrated via `FormOptionsTrait::addWithDefaults()` and demo translations (EN, ES, FR, DE).
- **PHPUnit** — Expanded unit tests (CSS utilities, model transformers, DI, static types, controller trait); PHP line coverage **99.59%** (TS/JS remains **100%**).

### Changed

- **Platform wording** — README and **INSTALLATION** state minimum **PHP 8.2** and **Symfony 7.4**; Symfony 8.0 / 8.1 documented as requiring PHP 8.4+.
- **demo/symfony7** — Docker image targets **PHP 8.2**; **a2lix/translation-form-bundle** pinned to **^3.2** (PHP 8.2–compatible); `docker-compose` mounts parent `bundles/` at `/var/nowo-bundles` for Nowo path repos.
- **demo/symfony8** — `composer.json` requires **PHP >= 8.4**; same `/var/nowo-bundles` volume for Nowo path repos.
- **composer.json** — `homepage` / `support` URLs aligned with GitHub repo **FormKitBundle**.
- **Rector** — `readonly` classes and minor cleanups in transformers and related code (no behaviour change); `tests/Stubs` excluded from Rector dead-code rules.

### Fixed

- **demo/symfony7** — A2lix 3.x bundle config uses `locales` instead of `enabled_locales` (fixes HTTP 500 after `composer update`).

### Documentation

- **README** — Stars and coverage badges, “Found this useful?” line, documentation section reordered with **Additional documentation** subsection; demos list updated.
- **docs/USAGE.md**, **docs/ENGRAM.md**, **docs/DEMO-FRANKENPHP.md** — Table of contents for long pages.
- **demo/README** — Minimum PHP/Symfony per demo, Nowo special fields page and path-repo setup.
- **docs/CHANGELOG** — Release compare links use `nowo-tech/FormKitBundle`.
- Removed duplicate **docs/README.md** (content lives under root README and linked docs).

## [2.0.2] - 2026-06-11

### Fixed

- **demo/symfony7** — Web Profiler dev routes use `wdt.php` / `profiler.php` instead of removed XML routes (Symfony 7.4+); fixes `cache:clear` and demo startup after `composer update`.
- **demo/symfony7 / symfony8 Makefiles** — Missing `)` in `update-deps` include broke `make up` and other targets.
- **demo/Makefile** — Same missing `)` in `update-deps-all` include.

### Changed

- **Demo Docker entrypoints** — Run `importmap:install` after Composer so Asset Mapper vendor assets (e.g. `@hotwired/stimulus`) are present; avoids HTTP 500 on demo home after dependency updates.
- **demo/symfony7 / symfony8** — `composer.lock` and `config/reference.php` synced with Symfony **7.4.*** / **8.1.*** pins.
- **Demo `.gitignore`** — Ignore generated `assets/vendor/` (importmap vendors).

### Documentation

- **demo/README**, **DEMO-FRANKENPHP**, **UPGRADING** — Document demo `update-deps` behaviour and importmap setup.

## [2.0.1] - 2026-06-11

### Removed

- **demo/symfony6** — Symfony 6 demo removed from the repository (bundle 2.x requires Symfony 7.4+). Use **demo/symfony7** or **demo/symfony8**. Does not affect the Composer package (`demo/` is excluded from the archive).

### Changed

- **Make / update-deps** — Demo dependency updates run only for **symfony7** and **symfony8**; root and demo Makefiles no longer reference symfony6.

### Documentation

- **demo/README**, **DEMO-FRANKENPHP**, root **Dockerfile** comment — Aligned with Symfony 7.4 / 8.x demos only.

## [2.0.0] - 2026-06-11

### Added

- **FormKitControllerTrait** — build forms in controllers with the same merge pipeline and Phase 2 helpers (`addTextType()`, …).
- **Help modal** — `help_modal` field option, **HelpModalExtension**, bundled `help-modal.js` / `help-modal.css`, optional Twig shells (Bootstrap 4/5, Tailwind, Foundation), configurable via `configs.<name>.help_modal`.
- **Choice presets** — `addSelect()`, `addMultiSelect()`, `addChoiceRadios()`, `addChoiceCheckboxes()`, `addMultiSelectSelectAll()` (optional **nowo-tech/select-all-choice-bundle**).
- **FQCN helpers** — `addAutocompleteField()` / `addCKEditorField()` (and controller `*Type` variants).
- **Model transformers** — `addSwitchType()`, `addJsonType()`, `addBoolType()`, `addMoneyType()`, `addCsvType()` with dedicated transformer classes.
- **ConstraintDefinitionFactory** — merge validation constraints from YAML `field_types` config.
- **CSS utilities** — `CssClassUtilities` and framework implementations (Bootstrap, Tailwind, Foundation) for help-modal styling.
- **Form type extensions** — **InputGroupExtension**, **RequiredLabelSuffixExtension** (documented in CONFIGURATION).
- **StaticHtmlType**, **FormFieldOptionsHelper**, expanded static block types.
- **Frontend tests** — Vitest coverage for `help-modal.js` (logger, modal behaviour).
- **QA tooling** — PHPStan, Rector, Scrutinizer config; expanded PHPUnit suite.
- **Demos** — FrankenPHP Docker setup (dev Caddyfile vs production worker), Symfony **7.4** and **8.1** demos; locale routing (`en` / `es` / `fr` / `de`), pages for data transformers, choice fields, CKEditor, UX Autocomplete, Dropzone, Cropper, translations, multi-step wizard.

### Changed

- **Platform requirements:** PHP **>= 8.2** (was 8.1). Symfony **^7.4 || ^8.0** only (7.4, 8.0, 8.1). Symfony 6.x and 7.0–7.3 are no longer supported.
- **CI** — Matrix: PHP 8.2–8.5 × Symfony 7.4, 8.0, 8.1 (Symfony 8 requires PHP 8.4+).
- **a2lix/translation-form-bundle** moved from `require` to Composer **suggest** (optional `translations` type).
- **Rector** — PHP set raised to 8.2.

### Documentation

- **README, INSTALLATION, USAGE, CONFIGURATION, demo/README, ROADMAP, DEMO-FRANKENPHP** — Aligned with current features, compatibility matrix (PHP/Symfony), choice presets, optional integrations (Select All Choice, FOSCKEditor, UX Autocomplete), help modal, demo locales and pages.
- **UPGRADING** — Migration guide from 1.x to 2.0.
- **Bundle templates / PHP** — Comments in English only.

## [1.0.0] - 2025-03-03

### Added

- **FormOptionsMerger** and **FormOptionsTrait**: convention-based label, placeholder, help keys (`form_snake.field_snake.*`), configurable `translation_domain`, `defaults.attr`, `defaults.row_attr`, and cascading merge (config → field type → field options).
- **Multiple configs:** `configs` with named entries (each with `alias`, `translation_domain`, `defaults`, `field_types`) and `default_config`. Forms can select a config via `setFormKitConfigName()`.
- **Phase 2 helpers** on FormOptionsTrait and FormKitTrait: `addText()`, `addEmail()`, `addTextarea()`, `addPassword()`, `addUrl()`, `addInteger()`, `addNumber()`, `addCheckbox()`, `addChoice()`. Pass only field name and options; no form type class needed.
- **buildFormFromArray():** Define all fields in one array; supported in FormOptionsTrait (type = FQCN) and FormKitTrait (type = snake_case).
- **FormTypeMap** and **type_map** config: map snake_case type names to form type FQCNs. Built-in types plus optional types when the package is installed: `dropzone` (Symfony UX Dropzone), `cropper` (Symfony UX Cropper.js), `translations` (A2lix TranslationFormBundle). Custom types via `nowo_form_kit.type_map`.
- **FormKitTrait** and **FormKitAbstractType:** alternative path using snake_case type names and FormTypeMap, now aligned with FormOptionsMerger and the same `configs` / `default_config` model.
- **Demos** (Symfony 6, 7, 8): FormType example (all field types, buildFormFromArray), form built in controller with `FormOptionsMerger::resolve()`, **Search form** (inline/horizontal layout), **Example form** (card/stacked layout).

### Changed

- Legacy root-level `translation_domain`, `defaults`, `field_types` are normalized into a single `default` config when `configs` is not set.
- **a2lix/translation-form-bundle** constraint relaxed to `^3.2 || ^4.0` so PHP 8.2 projects can use 3.x (4.x requires PHP 8.4).
- **Makefile:** Docker Compose now uses `--project-directory $(CURDIR)` so `/app` in the container always mounts the bundle root; added `down-dev` target to stop the dev container.

[2.0.20]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.20
[2.0.19]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.19
[2.0.18]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.18
[2.0.17]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.17
[2.0.16]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.16
[2.0.15]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.15
[2.0.14]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.14
[2.0.13]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.13
[2.0.12]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.12
[2.0.11]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.11
[2.0.10]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.10
[2.0.9]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.9
[2.0.8]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.8
[2.0.7]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.7
[2.0.6]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.6
[2.0.5]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.5
[2.0.4]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.4
[2.0.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.3
[2.0.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.2
[2.0.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.1
[2.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.0
[1.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v1.0.0
