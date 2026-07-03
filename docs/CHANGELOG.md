# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[2.0.4]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.4
[2.0.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.3
[2.0.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.2
[2.0.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.1
[2.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.0
[1.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v1.0.0
