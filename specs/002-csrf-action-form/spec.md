# Feature Specification: `csrf_action_form()` Twig function

**Feature Branch**: `002-csrf-action-form`  
**Status**: Implemented (v2.11.0)  
**Package**: `nowo-tech/form-kit-bundle`  
**Configuration**: `nowo_form_kit.twig.csrf_action_form` (`bool`, default `true`)  
**Origin**: Symfony Beacon spec 137 (FR-004, KIT-CSRF-ACTION-FORM) — host `App\Shared\Twig\CsrfActionTwigExtension::csrfActionForm()` moved to the kit.  
**Baseline requirement**: FR-TWIG-002 in [`../001-baseline/spec.md`](../001-baseline/spec.md)

---

## Summary

Templates (shared includes, kit forks, list row buttons) need small CSRF-protected POST forms without the controller passing a form. The bundle already ships `CsrfOnlyFormFactory`; this feature exposes it to Twig with the exact signature hosts already use, so host templates keep working unchanged when the host extension is deleted.

Only `csrf_action_form` is moved. The host's `get_filter_form`, `search_query_form` and `flat_hidden_fields` functions are out of scope (unused).

## User Scenarios

### US-01 — Named CSRF-only form (P1)

**Given** `{% set form = csrf_action_form(path('x'), 'x_id') %}`, **Then** the view is named `csrf_only`, method `POST`, with token field `csrf_only[_token]` for token id `x_id`.

### US-02 — Flat kit form (P1)

**Given** `csrf_action_form(url, 'id', named: false, csrfFieldName: '_csrf_token')`, **Then** the view has an empty name and a flat `_csrf_token` field.

### US-03 — CSRF + fields (P1)

**Given** `csrf_action_form(url, 'id', fields: {redirect: '/back'})` (optionally `fieldTypes` / `fieldOptions`), **Then** a `HiddenFieldsCsrfType` view with flat `redirect` (value `/back`, `null` → `''`) and flat `_token`; `named` is ignored.

### US-04 — Method (P2)

**Given** `method` `'get'`, **Then** the view method is `GET`.

### US-05 — Coexistence with a host function (P1)

**Given** a host that still registers its own `csrf_action_form`, **Then** container boot and rendering do not fail (Twig keeps the last registered definition; no exception on duplicate names). **Given** `twig.csrf_action_form: false`, **Then** the bundle extension service is not registered.

## Functional Requirements

- **FR-001**: `Nowo\FormKitBundle\Twig\CsrfActionFormExtension` exposes exactly one function, `csrf_action_form(string $action, string $csrfTokenId, string $method = 'POST', bool $named = true, string $csrfFieldName = '_token', array $fields = [], array $fieldTypes = [], array $fieldOptions = []): FormView`.
- **FR-002**: Dispatch — `fields !== []` → `CsrfOnlyFormFactory::createWithFields()`; else `named` → `createNamed()`; else `create()`; the result's `createView()` is returned.
- **FR-003**: `FormKitExtension` registers the service (private, tag `twig.extension`, argument `CsrfOnlyFormFactory`) when `twig.csrf_action_form` is `true` (default) and `Twig\Extension\AbstractExtension` exists; parameter `nowo_form_kit.twig.csrf_action_form` reflects the effective state.
- **FR-004**: Stateless service (FrankenPHP worker safe).

## Decision — default `true`

Twig's `ExtensionSet` stores functions in a name-keyed map, so a duplicate name silently overrides instead of throwing. Enabling by default is therefore backwards compatible for hosts that kept a copied extension (same signature and behaviour), and lets new hosts drop their copy. The flag exists to switch the bundle function off explicitly.

## Success Criteria

- Unit tests: named, flat, custom CSRF field name, fields (+ types / options / null value), method normalisation, Twig rendering (positional and named arguments), DI registration on/off.
- 100% line coverage kept for the new sources.
