# Roadmap

This document outlines the planned direction for Form Kit Bundle. Items are grouped by horizon and are subject to change based on feedback and maintainer capacity.

---

## Current state (v1.x)

- **FormOptionsTrait** + **FormOptionsMerger**: primary path; convention-based labels/placeholder/help; multiple configs; Phase 2 helpers; `buildFormFromArray()` with FQCN types.
- **FormKitTrait** + **FormTypeMap**: snake_case type names; optional UX/A2lix types (dropzone, cropper, translations); `type_map` config; `buildFormFromArray()` with string types.
- **FormKitAbstractType**: base type using FormKitTrait; uses FormOptionsMerger + FormTypeMap for snake_case types with the same config model (`configs` / `default_config`).
- **Demos**: Symfony 6, 7, 8 with FormType, controller form, Search form (inline layout), Example form (card layout).

---

## Short term

- **Stabilise 1.0**
  - Finalise docs and changelog; release 1.0.0.
  - Submit recipe to [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) when the package is on Packagist.
- **FormKit path hardening**
  - Add dedicated examples/tests for FormKitAbstractType + FormTypeMap (snake_case types) to ensure long-term parity with FormOptionsTrait.
- **Optional types**
  - Add more optional types to FormTypeMap when relevant packages are present (e.g. other Symfony UX or popular form bundles), with clear docs and `class_exists` guards.
- **Testing**
  - Broaden test coverage (integration tests for demos, FormOptionsMerger with multiple configs, FormTypeMap with type_map).

---

## Medium term

- **Helpers for optional types**
  - Add helpers such as `addDropzone()` on FormOptionsTrait/FormKitTrait when `symfony/ux-dropzone` is available, delegating to the type map and keeping the bundle optional-dependency free.
- **Form-level defaults**
  - Support form-level default options in config (e.g. `by_form[contact]`) so that all fields of a given form inherit options without repeating per field.
- **Validation / constraints**
  - Optional convention or helpers for common validation (e.g. mapping form name + field to constraint messages) without replacing Symfony Validator.
- **Layout / themes**
  - Document or provide examples for common layouts (horizontal, floating labels, grid) and theme configs (Bootstrap 5, Tailwind) using existing `defaults` and `row_attr`.

---

## Long term / ideas

- **PHP attributes**
  - Explore attributes on form types or properties to select config, translation domain, or field options (e.g. `#[FormKitConfig('bootstrap')]`) where it fits Symfony’s form and DI model.
- **Symfony UX Live**
  - Ensure compatibility and, if useful, small examples for forms used with Live Components (e.g. re-validation, form options merge with dynamic fields).
- **Backward compatibility**
  - Keep supporting Symfony 6.4 as long as feasible; document supported versions and upgrade path in UPGRADING.md when dropping a major Symfony version.

---

## Non-goals

- Replacing or wrapping the Symfony Form component.
- Providing a full UI component library (only form option and convention layer).
- Built-in frontend assets or JavaScript (bundle stays backend-only).

---

If you want to influence the roadmap, open an issue or a discussion in the project repository.
