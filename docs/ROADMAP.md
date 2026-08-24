# Roadmap

This document outlines the planned direction for Form Kit Bundle. Items are grouped by horizon and are subject to change based on feedback and maintainer capacity.

## Table of contents

- [Current state (v2.x)](#current-state-v2x)
- [Short term](#short-term)
- [Medium term](#medium-term)
- [Long term / ideas](#long-term-ideas)
- [Non-goals](#non-goals)

---

## Current state (v2.x)

Current tag **v2.5.0**.

- **Options** strategy (`FormOptionsTrait` + FQCN): primary path; `profiles` / `default_profile`; `by_form`; `constraint_message_convention`; `#[FormKitConfig]`; **bound-builder** / **array-build**.
- **Kit** strategy (`FormKitTrait` / `FormKitAbstractType` + `FormTypeMap`): snake_case types; same merge model.
- **Controller** strategy (`FormKitControllerTrait`) and **Wrapped** strategy (`AbstractFormKitWrappedType`).
- Choice presets, model transformers, help modal, optional UX helpers (Autocomplete, CKEditor 4, Dropzone, Cropper) and optional nowo-tech widgets (OTP, phone, password toggle/strength, icon selector, CKEditor 5, Tiptap, tag input, slide-to-confirm).
- **Wizard / CSRF-only / GET filters:** `MultiStepFormBuilder`, `CsrfOnlyFormFactory`, `GetFilterFormFactory`, `SearchQueryType`, static field types.
- **Demos**: **demo/symfony8** — see [USAGE — strategies](USAGE.md#usage-strategies) for the naming used in docs.
- **Flex recipe** stub: [RECIPE.md](RECIPE.md).

---

## Short term

- **Recipe on recipes-contrib**
  - Open a PR to [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) from the maintained stub (config + Twig `form_themes` for `static_blocks`). Tracked in [RECIPE.md](RECIPE.md).
- **FormKit path hardening**
  - Add dedicated examples/tests for FormKitAbstractType + FormTypeMap (snake_case types) to ensure long-term parity with FormOptionsTrait.
- **Testing**
  - Broaden test coverage (integration tests for demos, FormOptionsMerger with multiple profiles, FormTypeMap with type_map).

---

## Medium term

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
