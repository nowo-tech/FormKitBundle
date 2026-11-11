# Roadmap

This document outlines the planned direction for Form Kit Bundle. Items are grouped by horizon and are subject to change based on feedback and maintainer capacity.

## Table of contents

- [Current state (v2.x)](#current-state-v2x)
- [Short term](#short-term)
- [Medium term](#medium-term)
- [Long term / ideas](#long-term--ideas)
- [Ideas from an admin-template review (2026-11)](#ideas-from-an-admin-template-review-2026-11)
  - [Choices and toggles](#choices-and-toggles)
  - [Text inputs](#text-inputs)
  - [Numbers, dates, colours, files](#numbers-dates-colours-files)
  - [Layouts and long forms](#layouts-and-long-forms)
  - [Wizards](#wizards)
  - [Generic types for common admin screens](#generic-types-for-common-admin-screens)
  - [Routed elsewhere](#routed-elsewhere)
- [Non-goals](#non-goals)

---

## Current state (v2.x)

Current tag **v2.5.3**.

- **Options** strategy (`FormOptionsTrait` + FQCN): primary path; `profiles` / `default_profile`; `by_form`; `constraint_message_convention`; `#[FormKitConfig]`; **bound-builder** / **array-build**.
- **Kit** strategy (`FormKitTrait` / `FormKitAbstractType` + `FormTypeMap`): snake_case types; same merge model.
- **Controller** strategy (`FormKitControllerTrait`) and **Wrapped** strategy (`AbstractFormKitWrappedType`).
- Choice presets, model transformers, help modal, optional UX helpers (Autocomplete, CKEditor 4, Dropzone, Cropper) and optional nowo-tech widgets (OTP, phone, password toggle/strength, icon selector, CKEditor 5, Tiptap, Milkdown, tag input, slide-to-confirm, ALTCHA, barcode, UiKit date range).
- **Wizard / CSRF-only / GET filters:** `MultiStepFormBuilder`, `CsrfOnlyFormFactory`, `GetFilterFormFactory`, `SearchQueryType`, static field types.
- **FrankenPHP worker:** compatible with kernel reuse (`FRANKENPHP_RESET_KERNEL` unset/false); see [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).
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

## Ideas from an admin-template review (2026-11)

In November 2026 we reviewed the public demo of a commercial admin template. We took feature ideas only: no code,
CSS, markup, icons, images or asset names. This list keeps the ideas that are real gaps in FormKit today. Ideas
that belong to UiKit or to another bundle are routed at the end.

**Rules for every item.** It works without JavaScript (the script only enhances). It is CSP-safe: no inline script,
no inline style, no `on*` attributes. It meets WCAG 2.2 AA (keyboard, labels, `aria-*`, visible focus). It uses UiKit
tokens, so light and dark themes follow. Its strings ship in the 7 locales (en, es, fr, de, it, pt, nl). It is
FrankenPHP worker-safe: no per-request state in services. It is opt-in when it changes existing markup. Items that
need a script ship as an optional Stimulus peer or IIFE; when the first lands, the non-goal on frontend assets is
reworded.

**Priority.** P1 = next minor releases. P2 = when a host asks or a related item is open. P3 = idea, no plan yet.
**Size.** S = a few days. M = one to two weeks. L = more.

### Choices and toggles

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P1 | **Choice cards** — an expanded `ChoiceType` (radio or checkbox) drawn as selectable cards: title, short text, optional icon or image. | Plan, shipping and option pickers are common in admin and checkout forms; today each host writes its own theme block. | M | FormKit theme block (`choice_blocks`) + UiKit card tokens | Native input inside the `<label>`; checked state through CSS only; images need `alt`; works with `choice_attr` and translation parameters. |
| P1 | **Framework-neutral switch** — a `switch` option on checkbox rows: `role="switch"`, sizes, optional on/off icon. | FormKit has no switch rendering of its own; hosts rely on the CSS framework's switch class, and Tailwind or plain hosts get a checkbox. | S | FormKit (`CheckboxRowExtension`) + UiKit tokens | Stays a real checkbox; label and state read by screen readers; no colour-only state. |
| P2 | **Segmented choice** — render a small single choice as a UiKit segmented control. | Short choices (period, view mode, monthly or yearly) read better as a button group than as a select. | S | FormKit option + UiKit `_segmented` | Radio group underneath; arrow keys move; falls back to radios. |
| P2 | **Rich options in the combobox** — option icon, subtext, group headers, a selection limit and a "no results" text. | Long selects (users, countries, roles) need context per option and a cap on picks. | M | FormKit combobox peer | Option data comes from `choice_attr`, escaped; limit also enforced server side (`Count`). |
| P3 | **Rating field** — a radio group drawn as stars (1 to N). | Reviews, feedback and QA scores. | S | FormKit type; read-only display in UiKit | Radios with labels ("3 of 5"); arrow keys; no half steps at first. |

### Text inputs

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P1 | **Character counter** — "42 / 160" under a field, taken from the `Length` constraint or the `maxlength` attribute. | SEO titles, SMS text, bios: users need the limit before they submit. | S | FormKit extension + small peer | Without JS the limit shows in the help text; count announced with `aria-live="polite"` only near the limit; counts graphemes, not bytes. |
| P2 | **Auto-growing textarea** — the textarea grows with its content. | Notes and comments without a scroll box inside the page. | S | FormKit option | CSS `field-sizing: content` first; a tiny peer only for older browsers; a max height keeps it bounded. |
| P2 | **Input formatting (masks)** — display formatting for IBAN, card-like groups, dates, times and locale numbers. | Fewer typing errors on long codes; numbers show the locale grouping. | M | FormKit peer + existing model transformers | Never blocks paste; sets `inputmode`; the server transformer and constraints stay the source of truth; phone stays in PhoneInput. |
| P2 | **More input-group addons** — text on both sides, a button addon, a select addon, a merged style. | Prices (€ … .00), URLs (https:// …), "copy" or "generate" buttons next to a field. | S | FormKit (`InputGroupExtension`) | Addon text linked with `aria-describedby`; button addons are real `<button type="button">`. |
| P2 | **Floating labels for every framework** — one neutral theme, not only Bootstrap 5. | Compact forms on Tailwind and plain hosts. | S | FormKit theme + UiKit tokens | Label stays a real `<label>`; placeholder never replaces it; works with autofill. |
| P3 | **Speech-to-text button** — a microphone button that fills a text field. | Field work and accessibility for long notes. | S | FormKit peer | Hidden when the browser has no support; opt-in per field because the browser may send audio to a third party. |

### Numbers, dates, colours, files

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P1 | **Date and time conventions** — native `date`, `time`, `datetime-local`, `month` and `week` inputs with `min`, `max` and `step` taken from the constraints. | Correct pickers on every device without a JS picker; the limits match the server rules. | M | FormKit (option merger); date ranges stay in UiKit `DateRangeType` | Locale display comes from the browser; disabled days are checked again on the server. |
| P1 | **File upload field** — drop zone, list of chosen files with size and type, remove before submit, hints from the `File` / `Image` constraints. | Every admin uploads files; the optional Dropzone helper needs `symfony/ux-dropzone` and does not read the constraints. | M–L | FormKit type + peer; storage in StorageKit; crop in UiKit image crop | Plain `<input type="file">` without JS; the drop zone is a labelled button; errors per file in text; no upload before submit unless the host opts in. |
| P2 | **Range slider** — native range with a live value, min and max labels and ticks; a two-handle range built from two inputs. | Price, quantity and threshold filters. | M | FormKit type | `<output>` linked to the input; keyboard steps; two-handle variant keeps two named fields. |
| P2 | **Colour field with swatches** — native colour input plus a preset list. | Brand, label and status colours. | S | FormKit option; UiKit `AppearancePaletteType` stays for the theme | Swatches are radios with names, not colour-only; hex value shown as text. |

### Layouts and long forms

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P1 | **Layout profiles** — `layout: vertical / horizontal / inline` as a profile option, with the label column width, for every framework. | Today horizontal forms are copy-paste examples in USAGE. | M | FormKit profiles + theme | Same markup order for screen readers; collapses to vertical on small screens. |
| P1 | **Sticky form actions** — the submit and cancel bar stays visible on long forms. | Long settings and checkout forms lose their main button below the fold. | S | FormKit `actions` block + UiKit tokens | CSS `position: sticky` only; never covers the focused field; respects the UiKit footer. |
| P1 | **Error summary** — a list of errors at the top of the form after submit, each linking to its field; focus moves to it. | WCAG 3.3.1 and long forms where the error is off screen. | S | FormKit theme block + 7-locale title | Works without JS (server render); `role="alert"` once; links use the field ids. |
| P2 | **Client-side hints from constraints** — map `NotBlank`, `Length`, `Regex`, `Range`, `Email` to `required`, `minlength`, `maxlength`, `pattern`, `min`, `max`, `type`. | Instant browser feedback with no extra code; the server still validates. | M | FormKit option merger | Opt-in (`novalidate` hosts keep today's flow); messages stay server-side in the 7 locales. |
| P2 | **Numbered sections** — fieldset cards with a number and a title ("1. Address", "2. Delivery"). | Long one-page forms read as steps without a wizard. | S | FormKit static blocks + UiKit card | Real `<fieldset>` + `<legend>`. |
| P2 | **Collection repeater** — add, remove and reorder rows of a `CollectionType`. | Line items, contacts, addresses; every host rewrites the prototype script. | M | FormKit peer + theme; reorder through UiKit sortable | Without JS an "add row" submit button re-renders the form; focus goes to the new row; remove has an undo or a confirm. |
| P2 | **Unsaved-changes guard** — warn before leaving a changed form. | Avoid lost edits in long admin forms. | S | FormKit peer | Opt-in; off after submit; no warning on untouched forms. |

### Wizards

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P1 | **Review step** — a last step that shows all earlier answers read-only, grouped by step, each group with an "edit" link. | Users check before they commit (orders, onboarding, setup). | M | FormKit `MultiStepFormBuilder`; layout in the UiKit wizard page | Values printed escaped; secret fields masked; edit links go to the step URL. |
| P2 | **Step errors on the indicator** — a step with errors shows a mark and a count in the step list. | The user sees where to go back. | S | FormKit session + UiKit wizard steps | Text, not only colour; `aria-current="step"` stays on the current step. |
| P3 | **Wizard inside a dialog** — a multi-step form in a UiKit dialog. | "Create app" or "invite member" flows without leaving the list. | M | FormKit + UiKit remote form dialog (multi-step already in 2.33) | Recipe only, unless a gap shows. |

### Generic types for common admin screens

| Pri | Idea | Why for a Symfony host | Size | Fit | Constraints |
|-----|------|------------------------|------|-----|-------------|
| P2 | **Checkbox matrix** — rows × columns of checkboxes (for example module × read / write / create), with toggles per row, per column and for all. | Role editors and notification settings (channel × event). | M | FormKit type; RbacKit uses it for permissions | A real `<table>` with headers; each checkbox named "Module, action"; toggles are peers, the matrix works without them. |

### Routed elsewhere

| Idea seen | Owner |
|-----------|-------|
| Tags, emails list, user list with suggestions | TagInputBundle |
| Phone with country selector, SMS OTP | PhoneInputBundle, OtpInputBundle |
| Password show/hide, strength meter, requirements checklist | PasswordToggleBundle, PasswordStrengthBundle, PasswordPolicyBundle |
| Select all / deselect all | SelectAllChoiceBundle |
| Rich text editors | Ckeditor5EditorBundle, TiptapEditorBundle, MilkdownEditorBundle |
| Icon picker | IconSelectorBundle |
| Slide to confirm | SlideToConfirmBundle |
| Date-range presets (today, last 7 days, this month) | UiKitBundle (`DateRangeType`) |
| Image crop on upload | UiKitBundle (image crop) |
| Card number, expiry, CVV fields | Not planned: payment-provider fields keep hosts out of PCI scope |

---

## Non-goals

- Replacing or wrapping the Symfony Form component.
- Providing a full UI component library (only form option and convention layer).
- Built-in frontend assets or JavaScript beyond the optional help modal.

---

If you want to influence the roadmap, open an issue or a discussion in the project repository.
