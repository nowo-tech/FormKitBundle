# Roadmap

This document outlines the planned direction for Form Kit Bundle. Items are grouped by horizon and are subject to change based on feedback and maintainer capacity.

---

## Current state (v2.x)

- **FormOptionsTrait** + **FormOptionsMerger**: primary path; convention-based labels/placeholder/help; multiple configs; `by_form` defaults; optional constraint message convention; `#[FormKitConfig]`; Phase 2 helpers; `buildFormFromArray()` with FQCN types; `withBuilder()` + `add*Field()`.
- **FormKitTrait** + **FormTypeMap**: snake_case type names (core Symfony types + optional UX/A2lix); `type_map` config; `buildFormFromArray()` with string types; `#[FormKitConfig]`.
- **FormKitAbstractType**: base type using FormKitTrait; uses FormOptionsMerger + FormTypeMap for snake_case types with the same config model (`configs` / `default_config`).
- **FormKitControllerTrait**, choice presets, model transformers, help modal (JS + Twig shells), optional helpers (Autocomplete, CKEditor, Dropzone, Cropper).
- **Demos**: **demo/symfony8** (PHP 8.4+) with FormType, controller form, Search, Example, Dropzone, Cropper, translations, nested form, data transformers, choice fields, conditional fields (+ Live Component), Kit API patterns, **Nowo special fields**, CKEditor (FOS), UX Autocomplete, multi-step wizard; locales `en` / `es` / `fr` / `de`.
- **Flex recipe** (repo stub under `.symfony/recipe/`): see [docs/RECIPE.md](RECIPE.md) for submitting to [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib).

---

## Short term

- **Recipe on recipes-contrib**
  - Open a PR to [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) from the maintained stub (config + Twig `form_themes` for `static_blocks`). Tracked in [RECIPE.md](RECIPE.md).
- **FormKit path hardening**
  - Add dedicated examples/tests for FormKitAbstractType + FormTypeMap (snake_case types) to ensure long-term parity with FormOptionsTrait.
- **Testing**
  - Broaden test coverage (integration tests for demos, FormOptionsMerger with multiple configs, FormTypeMap with type_map).

---

## Medium term

- **More optional type_map entries** — additional UX / ecosystem types when packages are present.
- **Layout recipes** — more copy-paste Twig patterns (input groups + grid, Foundation) beyond Bootstrap/Tailwind examples in USAGE.
- **Richer attributes** — optional attribute-driven field options / translation domain beyond `#[FormKitConfig]`.

---

## Long term / ideas

- **Backward compatibility**
  - Document supported Symfony versions and upgrade path in UPGRADING.md when dropping a major Symfony version.

---

## Non-goals

- Replacing or wrapping the Symfony Form component.
- Providing a full UI component library (only form option and convention layer).
- Built-in frontend assets or JavaScript beyond the optional help modal.

---

If you want to influence the roadmap, open an issue or a discussion in the project repository.
