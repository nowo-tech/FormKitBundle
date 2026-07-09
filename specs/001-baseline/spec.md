# Feature Specification: FormKitBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Status**: Active  

**Package**: `nowo-tech/form-kit-bundle`  
**Configuration root**: `nowo_form_kit`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Symfony bundle to **reduce repetitive form field options**: convention-based translation keys (`form_snake.field_snake.label|placeholder|help`), named YAML configs with cascading merge (global → field type → form → field), CSS-framework-aware class utilities, optional help-modal UX, static form blocks, model transformers, and multi-step wizard helpers.

---

## User Scenarios

### US-01 — Convention-based labels (P1)

**Given** a form using `FormOptionsTrait` with `FormOptionsMerger`, **When** `addText($builder, 'full_name')` runs without explicit label, **Then** translation keys resolve to `{form_snake}.full_name.label` (and `.placeholder`, `.help` when enabled).

### US-02 — Named configs & cascading merge (P1)

**Given** multiple entries under `nowo_form_kit.configs`, **When** a form calls `setFormKitConfigName('bootstrap')`, **Then** `FormOptionsMerger` merges config defaults → field-type options → form options → field options with later keys winning.

### US-03 — Phase-2 field helpers (P1)

**Given** `FormKitTrait` or `FormOptionsTrait`, **When** integrator calls `addEmail()`, `addChoice()`, `addSwitchType()`, etc., **Then** mapped Symfony types and transformers apply with merged options and constraint definitions from config.

### US-04 — Help modal & CSS frameworks (P2)

**Given** `help_modal` enabled in config and field option `help_modal` set, **When** the form renders, **Then** `HelpModalExtension` + `help-modal.js` show framework-specific shells (Bootstrap 4/5, Tailwind, Foundation) using `CssClassUtilities` for the configured `css_framework`.

### US-05 — Multi-step wizard (P2)

**Given** a wizard built with `MultiStepFormBuilder`, **When** user advances steps, **Then** `MultiStepWizardSession` persists partial data between requests via the session factory.

---

## Requirements

### Bundle & config

- **FR-BUNDLE-001**: `NowoFormKitBundle` alias `nowo_form_kit`.
- **FR-CFG-001**: `Configuration` — `type_map`, `default_config`, `css_framework`, named `configs` (alias, translation_domain, required_label_suffix, help_modal, defaults, field_types).
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
- **FR-FORM-002**: `FormOptionsMerger`, `FormKitOptionMerger` — cascading option resolution.
- **FR-FORM-003**: `FormFieldOptionsHelper` — convention keys and disable flags.
- **FR-FORM-004**: `FormTypeMap` — built-in + configurable type name → FQCN map.
- **FR-FORM-005**: Form extensions — input group, required suffix, help modal.
- **FR-FORM-006**: Model transformers — switch, JSON, bool, money, CSV.
- **FR-FORM-007**: `ConstraintDefinitionFactory` — constraints from YAML config.
- **FR-FORM-008**: Static alert/HTML/separator types; optional A2lix `TranslationsFormsType`.

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

---

## Explicit non-goals

- Rendering forms outside Symfony Form component.
- Bundling optional third-party field types (CKEditor, UX Autocomplete, Select All Choice) — Composer **suggest** only.

---

## Validation

`composer qa`, PHPUnit, PHPStan, inventory row audit.
