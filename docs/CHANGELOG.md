# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
