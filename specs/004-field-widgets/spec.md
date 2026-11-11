# Feature Specification: field widgets (choice cards, switch, character counter)

**Feature Branch**: `feat/p1-lot1`  
**Status**: Implemented (target v2.22.0)  
**Package**: `nowo-tech/form-kit-bundle`  
**Origin**: [ROADMAP](../../docs/ROADMAP.md#ideas-from-an-admin-template-review-2026-11), "Ideas from an admin-template review (2026-11)", P1 lot 1 (owner approved; one minor per lot). Ideas only: no code, CSS, markup or assets were taken from the reviewed template.  
**Baseline requirement**: FR-ASSET-003 in [`../001-baseline/spec.md`](../001-baseline/spec.md)

---

## Summary

Three opt-in options draw common admin widgets with one framework-neutral markup:

| Option | Extension | Types | Block prefix |
|--------|-----------|-------|--------------|
| `choice_cards` | `ChoiceCardsExtension` | `ChoiceType` (expanded) and subtypes | `nowo_choice_cards` |
| `switch` | `SwitchExtension` | `CheckboxType` (not radios) | `nowo_switch` |
| `char_counter` | `CharCounterExtension` | `TextType` and subtypes | `nowo_char_counter` |

Blocks live in `@NowoFormKitBundle/form/field_widgets_theme.html.twig`, prepended to `twig.form_themes`
(`nowo_form_kit.field_widgets.form_theme`, default `true`). Styles: `field-widgets.css`; script: `char-counter.js`.

## Requirements

- **FR-001** Every option defaults to `false`; with the option unset, the view (block prefixes, vars) and the rendered
  markup are unchanged on `form_div_layout`, `bootstrap_5_layout`, `bootstrap_5_horizontal_layout`, `tailwind_2_layout`
  and UiKit's `nowo_ui_layout` (byte-identical test per theme).
- **FR-002** Works without JavaScript; `char-counter.js` only enhances. Without the CSS the widgets degrade to native
  radios, checkboxes and text.
- **FR-003** CSP-safe: no inline script, no inline style, no `on*` attribute in the markup; the script is an external
  IIFE.
- **FR-004** WCAG 2.2 AA: native inputs with a `<label for>`; cards: visible native control, description linked with
  `aria-describedby`, group `role="radiogroup"`/`group` labelled by the field label, images need `alt`; switch:
  `role="switch"`, label inside, state shown by the thumb position (and optional icons), focus ring on the track,
  forced-colors rules; counter: limit linked with `aria-describedby`, polite live region only near / over the limit,
  debounced.
- **FR-005** Colours, radius, focus and motion use UiKit tokens (`--nowo-ui-*`) with `currentColor` fallbacks;
  reduced-motion respected.
- **FR-006** New strings (`char_counter.*`) in en, es, fr, de, it, pt, nl.
- **FR-007** FrankenPHP worker mode with kernel reset false: the extensions keep no per-request state.
- **FR-008** Counter limits: option → field `Length` → mapped property `Length` (validator metadata) → `maxlength` /
  `minlength`; unit from `Length::$countUnit` (else graphemes); a textarea line break counts as CRLF; never adds
  `maxlength`.

## Out of scope (later lots)

Segmented choice, rich combobox options, rating, auto-growing textarea, masks (see the roadmap).
