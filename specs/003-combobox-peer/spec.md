# Feature Specification: opt-in combobox Stimulus peer

**Feature Branch**: `003-combobox-peer`  
**Status**: Implemented (v2.12.0)  
**Package**: `nowo-tech/form-kit-bundle`  
**Source**: `src/Resources/assets/stimulus-peers/combobox_controller.ts` (Stimulus identifier `combobox`)  
**Origin**: Symfony Beacon spec 138 (FR-010, KIT-COMBOBOX) — host `assets/controllers/combobox_controller.ts` (issue duplicate picker) moved to the kit.  
**Baseline requirement**: FR-ASSET-003 in [`../001-baseline/spec.md`](../001-baseline/spec.md)

---

## Summary

Hosts need a small searchable picker for a few dozen server-rendered options (e.g. "mark as duplicate of …") without Tom Select / UX Autocomplete. Beacon had a Stimulus controller that filters a static list and writes the selected value into a hidden input. The kit ships it as an **opt-in TypeScript Stimulus peer** (same pattern as UiKitBundle `stimulus-peers/`): not built, not registered, no PHP or Twig change; the host imports it into its own Stimulus app. The peer adds the WAI-ARIA 1.2 combobox pattern, keyboard navigation and a **neutral** default required message (Beacon's hard-coded `"Select an issue"` becomes `"Please select an option."`, overridable per instance).

`@hotwired/stimulus` stays a host dependency (kit dev dependency only, for Vitest / typecheck).

## Markup contract

| Kind | Name | Role |
|------|------|------|
| target | `query` | Visible text / search input (gets `role="combobox"`) |
| target | `value` | Input submitted with the form (usually hidden); receives the option's `data-value` |
| target | `list` | Listbox container (gets `role="listbox"`, toggled with `hidden`) |
| target | `option` | One option: `data-value` (required to be selectable), optional `data-label` (written into `query`; default trimmed text) and `data-search` (filter haystack; default text) |
| target | `empty` (optional) | Shown when no option matches |
| value | `open` (`Boolean`, `false`) | List visibility |
| value | `requiredMessage` (`String`, `''`) | `requireValue` message; empty → `data-required-message` on the query (Beacon compat) → `"Please select an option."` |
| value | `selectedClass` (`String`, `is-selected`) | Class on the chosen option (`''` = none) |
| value | `activeClass` (`String`, `is-active`) | Class on the keyboard-active option (`''` = none) |
| action | `filter`, `onQueryFocus`, `onQueryKeydown`, `select`, `open`, `close`, `toggle`, `requireValue` | See below |
| event | `combobox:change` | `detail: { value, label }`, after a selection; the `value` input also receives a bubbling `change` |

## User Scenarios

### US-01 — Filter and select (P1)

**Given** the query input with `input->combobox#filter`, **When** the user types, **Then** options whose `data-search` (else text) does not contain the needle (case-insensitive) are `hidden`, `empty` is shown when none match, and a selected value whose label no longer equals the query is cleared. **When** an option is clicked (`combobox#select`), **Then** `value` = `data-value`, `query` = label, the option gets `aria-selected="true"` + `selectedClass`, the list closes and focus returns to the query **without reopening the list**.

### US-02 — ARIA 1.2 (P1)

**Given** connect, **Then** missing attributes are filled (host values kept): query `role="combobox"`, `aria-autocomplete="list"`, `aria-controls` = list id, `autocomplete="off"`, `aria-expanded` in sync with `open`; list `role="listbox"` and an id; each option `role="option"`, an id and `aria-selected`. The keyboard-active option is exposed with `aria-activedescendant` on the query (focus stays in the input).

### US-03 — Keyboard (P1)

- `ArrowDown` / `ArrowUp`: open the list (if closed) and move the active option among visible ones, wrapping; on a closed list `ArrowUp` starts at the last option. `Alt+ArrowDown` only opens.
- `Enter` (list open): select the active option, else the first visible option when no value is set; otherwise the event is left to the form.
- `Escape`: close (default prevented only when the list was open). `Tab`: close.
- Pointer down outside the controller element closes the list.

### US-04 — Required value (P1)

**Given** `submit->combobox#requireValue` on the form and an empty `value`, **Then** submit is prevented, the list opens, the query is focused and its custom validity is set to the resolved message and reported.

## Functional Requirements

- **FR-001**: Default export `ComboboxController` (Stimulus `Controller`), named export `DEFAULT_REQUIRED_MESSAGE = 'Please select an option.'`.
- **FR-002**: Public action names and targets are a superset of Beacon's controller, so host markup keeps working after swapping the import.
- **FR-003**: No user-facing English besides the neutral fallback; all copy comes from host markup / values.
- **FR-004**: Not built into `src/Resources/public/`; covered by Vitest (100% statements / branches / functions / lines) and `tsc`.

## Differences from the Beacon host controller

- Default required message is neutral (`Please select an option.`) and configurable via `requiredMessage`; `data-required-message` still works.
- Fixes: the list no longer reopens after a selection (focus re-ran `onQueryFocus`), and the required message is no longer cleared by the focus that follows it.
- New: ARIA attributes, `aria-activedescendant`, arrow-key navigation, outside click, `toggle`, `combobox:change` / `change` events, `selectedClass` / `activeClass`.
- `Enter` on a **closed** list no longer auto-selects the first option (lets the form submit).

## Success Criteria

- Vitest: ARIA fill / preserve, filtering (search, text, empty target, stale value), selection (click, value-less option, events, classes), keyboard (arrows, wrap, Alt, Enter variants, Escape, Tab), outside pointer, required message resolution, disconnect cleanup.
