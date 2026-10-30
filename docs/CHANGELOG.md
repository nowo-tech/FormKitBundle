# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[2.15.0] - 2026-10-29](#2150---2026-10-29)
- [[2.14.1] - 2026-10-28](#2141---2026-10-28)
- [[2.14.0] - 2026-10-28](#2140---2026-10-28)
- [[2.13.3] - 2026-10-25](#2133---2026-10-25)
- [[2.13.2] - 2026-10-20](#2132---2026-10-20)
- [[2.13.1] - 2026-10-19](#2131---2026-10-19)
- [[2.13.0] - 2026-10-19](#2130---2026-10-19)
- [[2.12.0] - 2026-10-13](#2120---2026-10-13)
- [[2.11.0] - 2026-10-13](#2110---2026-10-13)
- [[2.10.0] - 2026-10-13](#2100---2026-10-13)
- [[2.9.0] - 2026-10-13](#290---2026-10-13)
- [[2.8.1] - 2026-10-13](#281---2026-10-13)
- [[2.8.0] - 2026-10-12](#280---2026-10-12)
- [[2.7.1] - 2026-10-09](#271---2026-10-09)
- [[2.7.0] - 2026-10-09](#270---2026-10-09)
- [[2.6.2] - 2026-10-09](#262---2026-10-09)
- [[2.6.1] - 2026-10-09](#261---2026-10-09)
- [[2.6.0] - 2026-10-07](#260---2026-10-07)
- [[2.5.4] - 2026-09-27](#254---2026-09-27)
- [[2.5.3] - 2026-09-24](#253---2026-09-24)
- [[2.5.2] - 2026-08-25](#252---2026-08-25)
- [[2.5.1] - 2026-08-24](#251---2026-08-24)
- [[2.5.0] - 2026-08-24](#250---2026-08-24)
- [[2.4.5] - 2026-08-24](#245---2026-08-24)
- [[2.4.4] - 2026-08-19](#244---2026-08-19)
- [[2.4.3] - 2026-08-19](#243---2026-08-19)
- [[2.4.2] - 2026-08-18](#242---2026-08-18)
- [[2.4.1] - 2026-08-17](#241---2026-08-17)
- [[2.4.0] - 2026-08-15](#240---2026-08-15)
- [[2.3.0] - 2026-08-14](#230---2026-08-14)
- [[2.2.0] - 2026-08-05](#220---2026-08-05)
- [[2.1.0] - 2026-08-04](#210---2026-08-04)
- [[2.0.20] - 2026-08-04](#2020---2026-08-04)
- [[2.0.19] - 2026-07-29](#2019---2026-07-29)
- [[2.0.18] - 2026-07-29](#2018---2026-07-29)
- [[2.0.17] - 2026-07-26](#2017---2026-07-26)
- [[2.0.16] - 2026-07-22](#2016---2026-07-22)
- [[2.0.15] - 2026-07-20](#2015---2026-07-20)
- [[2.0.14] - 2026-07-20](#2014---2026-07-20)
- [[2.0.13] - 2026-07-20](#2013---2026-07-20)
- [[2.0.12] - 2026-07-20](#2012---2026-07-20)
- [[2.0.11] - 2026-07-18](#2011---2026-07-18)
- [[2.0.10] - 2026-07-18](#2010---2026-07-18)
- [[2.0.9] - 2026-07-18](#209---2026-07-18)
- [[2.0.8] - 2026-07-18](#208---2026-07-18)
- [[2.0.7] - 2026-07-09](#207---2026-07-09)
- [[2.0.6] - 2026-07-09](#206---2026-07-09)
- [[2.0.5] - 2026-07-03](#205---2026-07-03)
- [[2.0.4] - 2026-07-03](#204---2026-07-03)
- [[2.0.3] - 2026-07-03](#203---2026-07-03)
- [[2.0.2] - 2026-06-11](#202---2026-06-11)
- [[2.0.1] - 2026-06-11](#201---2026-06-11)
- [[2.0.0] - 2026-06-11](#200---2026-06-11)
- [[1.0.0] - 2025-03-03](#100---2025-03-03)

## [Unreleased]

### Security

AI security audit (REQ-SEC-004) of v2.15.0 on 2026-10-30: **Pass (good) / Low**; all findings fixed here. See [UPGRADING](UPGRADING.md#unreleased) — two changes need host action when you use `help_modal`.

- **Help modal payloads are bound to the page (L1).** `help-modal.js` honoured any `label[data-nowo-help-modal]` inside a `<form>`, so sanitised user HTML that keeps `form` / `label` / `data-*` (e.g. DOMPurify defaults) rendered in a page could inject `icon_html` / `title_html` / `content` into `innerHTML` (stored XSS). Every payload now carries a per-request nonce (`HelpModalNonce`, kept in the main request attributes, worker-safe) and the script ignores payloads whose nonce differs from `<meta name="nowo-form-kit-help-modal-nonce">` in `<head>`, printed by the new Twig function `nowo_form_kit_help_modal_meta()` (`nowo_form_kit_help_modal_nonce()` returns the bare value). Same-origin Turbo fetches send the page nonce in `X-Nowo-Form-Kit-Help-Modal-Nonce` and the server reuses it for the fragment; Live Component morphs keep the last bound payload of a label.
- **Help modal text is escaped by default (L1).** `title` and `content` are inserted as text; HTML needs the explicit `title_html` (unchanged) or the new `content_html` option. `icon_html` stays trusted developer HTML.
- **Help modal portal only moves its own roots (L2).** Elements that merely carry `data-nowo-formkit-help-modal` are no longer relocated to `document.body`.
- **Browser logger off by default (I7).** `help-modal.js` diagnostics need `window.NOWO_FORM_KIT_DEBUG = true`; a payload parse error logs the length only, never the raw attribute.
- **Smaller dist archive (I8).** New `.gitattributes` `export-ignore`s `demo/` (demo `APP_SECRET`, `0.0.0.0` port), `tests/`, `specs/`, `.specify/`, `.scripts/`, `.githooks/`, the e2e suite, Vitest specs and CI / tooling files.
- **CSRF-only forms reject safe methods and empty token ids (I9).** `CsrfOnlyFormFactory::create()` / `createNamed()` / `createWithFields()` and `csrf_action_form()` throw `InvalidArgumentException` for any method other than POST / PUT / PATCH / DELETE (GET, HEAD, OPTIONS, TRACE, empty…) and for an empty or blank CSRF token id.
- **`stateless_csrf.form_types` rejects base types (I4).** `FormType` and every type under `Symfony\Component\Form\` / `Symfony\Bridge\` (e.g. `TextType`, `EntityType`) fail configuration: a stateless token id there would apply to every form. `%env()%` entries are rejected too (they would bypass the check).

### Changed

- `help-modal.js` rebuilt from TypeScript (Vitest 100% statements / branches / functions / lines kept).
- Demo: `base.html.twig` prints `nowo_form_kit_help_modal_meta()`; the contact form help modal uses `content_html`.

## [2.15.0] - 2026-10-29

FormKit: services tagged by autoconfiguration, translation parity check, docs/spec refresh (no API change) See [UPGRADING](UPGRADING.md#to-2150).

### Changed

- **Symfony wiring (REQ-SF-003):** the bundle form types, form type extensions and Twig extensions (`LocaleTabsTwigExtension`, `CsrfActionFormExtension`, `UnlabelledChoiceAriaLabelExtension`, the types and extensions of `services.yaml`) are now tagged through Symfony autoconfiguration (FrameworkBundle / TwigBundle) instead of manual `form.type` / `form.type_extension` / `twig.extension` tags. Service ids, classes, arguments and runtime behaviour are unchanged; only the per-type `stateless_csrf` extensions keep their explicit `extended_type` tag. See [UPGRADING](UPGRADING.md#unreleased).
- **Demo (REQ-DEMO-007):** `make update-bundle` (demo/symfony8) runs `composer update nowo-tech/form-kit-bundle` (without `--with-all-dependencies`), `dump-autoload`, `cache:clear` and `assets:install`; the demo `release-check` targets run it before the tests and the HTTP verification.

### Added

- **`make validate-translations` (REQ-MAKE-004):** YAML lint, `NowoFormKitBundle.<locale>.yaml`-only catalogues and key parity across the 7 locales (`.scripts/validate-translations.php`); part of `make release-check` and CI.

### Documentation

- USAGE: how applications override the bundle messages (`translations/NowoFormKitBundle.<locale>.yaml`) with the key list (REQ-I18N-001).
- SECURITY: scope, attack surface, threat model, secrets, logging, dependencies and exposure sections; release checklist item for the AI security audit (REQ-SEC-001 / REQ-SEC-002). `.github/SECURITY.md`: repository name and supported 2.x line (REQ-SEC-003).
- CONTRIBUTING: Cursor Agent settings (Ctrl+Enter submit, queued follow-ups; REQ-IDE-006) and translation validation.
- README `## Documentation`: base links in the canonical order, non-base docs under *Additional documentation* (REQ-DOCS-002); measured coverage (REQ-TEST-007).
- Tables of contents with every `##` / `###` section in the long docs (REQ-DOCS-005); `SPEC-DRIVEN-DEVELOPMENT.md` `REQ-*` table, user stories and scope (REQ-DOCS-013); Spec Kit baseline and inventory cover all 77 production files (REQ-SPECKIT-001 / REQ-SPECKIT-003).

### Fixed

- Twig-CS-Fixer lint is clean again: help-modal shells use the `include()` function instead of the `include` tag (same output) and `form_renderer.html.twig` hash spacing.
- Rector (`ExplicitNullableObjectInstanceRector`) in `ConstraintDefinitionFactory::constructorAccepts()` (no behaviour change).

### Tests

- PHPUnit docblock annotations (`@dataProvider`, `@runInSeparateProcess`, `@preserveGlobalState`) migrated to attributes (REQ-TEST-010); autoconfiguration test for the bundle services.
- Vitest: covers mutations that only add non-element nodes, restoring the 100% TypeScript coverage gate of `make test-ts` (the `help-modal.ts` early return was uncovered since 2.14.1).

## [2.14.1] - 2026-10-28

Security: the help modal only processes labels inside a form (no HTML from labels in user content). See [UPGRADING](UPGRADING.md#to-2141).

### Security

- **Help modal: only labels inside a `<form>`.** `help-modal.js` turned every `label[data-nowo-help-modal]` on the
  page into a trigger and inserted its `icon_html` / `title_html` / `content` as HTML, on load and on every DOM
  mutation. HTML sanitizers keep `<label>` and `data-*`, so sanitised user content (a comment, rich text) could carry
  a payload that ran script without a click. Labels outside a `<form>` are now ignored; Symfony form labels are
  always inside one.
- Docs: `input_group_prefix` / `input_group_suffix` are trusted HTML; `.github/SECURITY.md` lists 2.x as supported.

## [2.14.0] - 2026-10-28

Security: CSRF action forms (CsrfOnlyType, HiddenFieldsCsrfType, CsrfOnlyFormFactory, csrf_action_form) fail closed without the form CSRF extension. See [UPGRADING](UPGRADING.md#to-2140).

### Security

- CSRF action forms fail closed: `CsrfOnlyType` and `HiddenFieldsCsrfType` (and therefore `CsrfOnlyFormFactory` and the `csrf_action_form()` Twig function) now throw a `LogicException` when the form is built without Symfony's form CSRF extension (`form.type_extension.csrf`: `symfony/security-csrf` missing, `framework.csrf_protection: false` or `framework.form.csrf_protection: false`). Before, both types declared their own `csrf_protection` / `csrf_token_id` defaults, so a direct `createForm(CsrfOnlyType::class, …)` rendered no token and `isValid()` accepted any cross-site POST (the factory only failed by accident, with an `UndefinedOptionsException` on `csrf_field_name`). An explicit `csrf_protection: false` remains the caller's opt-out. New `Nowo\FormKitBundle\Form\CsrfProtectionGuard`; see [UPGRADING](UPGRADING.md#unreleased) and [SECURITY](SECURITY.md#csrf-action-forms-fail-closed).

### Added

- Developer tooling: `make release-check` now runs `check-no-raw-html-form` (REQ-TWIG-005) after the Twig `extra` gate; the check allowlists only the two form-theme files (`form/_locale_tabs.html.twig`, demo `nowo_special_fields_form_theme.html.twig`).

### Changed

- Demo: `translations`, `kit_api_patterns`, `multistep` and the shared `_form_sections_loop` partial render the form body through the rendered-aware child loop (`{% for child in form %}` + `not child.rendered` + `form_row(child)`) instead of field-by-field rows / `form_rest` / an unguarded loop; same fields, names and layout (REQ-TWIG-003 / REQ-TWIG-005).
- Docs: USAGE section "Rendering forms in Twig" states the Symfony Form / child-loop rule and that hosts and demos must not reintroduce raw `<form>` / `<input>` (REQ-TWIG-005 rule 9).

## [2.13.3] - 2026-10-25

Reproducible JS bundle: the build stamp is a hash of the sources instead of the build time. See [UPGRADING](UPGRADING.md#to-2133).

### Changed

- **Assets:** reproducible bundle build. `__FORM_KIT_HELP_MODAL_BUILD_TIME__` now holds a build id (a hash of the TypeScript/CSS sources and `pnpm-lock.yaml`) instead of the build time, so rebuilding unchanged sources (`make assets`, `make release-check`) no longer rewrites the committed JS; the console line reads `script loaded, build: <id>`.
- **Developer tooling (REQ-MAKE-002):** `make release-check` runs the full REQ-MAKE-002 chain in order: ensure-up now runs first and the demo release-check runs before test-ts (ensure-up, co-author and open-PR gates, check-twig-extra, composer-sync, cs-fix, cs-check, rector-dry, phpstan, igor, coverage-check, demo release-check, test-ts). New `make check-no-raw-html-form` target (`.scripts/check-no-raw-html-form.sh`, REQ-TWIG-005); it joins the chain once the remaining raw form markup is migrated or allowlisted as form-theme files.
- **Demo (REQ-TWIG-003):** the showcase pages (`choice_fields`, `ckeditor`, `conditional_fields`, `data_transformers`, `nowo_special_fields`, `ux_autocomplete`, `nested`, `locale_tabs`, `search`, `dropzone`, `cropper`) and the `ConditionalFieldsLive` component render their forms through the child loop instead of one `form_row(form.x)` per field: grid columns come from a field-name map, the `nested` section headings open at the field named in a map, `locale_tabs` emits the `_locale_tabs` include at the first `name_*` field and `search` keeps its input-group layout (rule 3); `form_end(form)` replaces `form_rest` + `render_rest: false`.

### Security

- **Dev dependencies:** the bundle and `demo/symfony8` pnpm lockfiles resolve `source-map-js` 1.2.2 (was 1.2.1, GHSA high) and `vite` 6.4.5 (was 6.4.1); the `vite` range is now `^6.4.5`. The rebuilt `help-modal.js` is byte-identical, so no shipped asset changes.

## [2.13.2] - 2026-10-20

Locale tabs heading styles (title, muted help, label gap) with UiKit tokens, UiKit muted slot on the help, UiKit 2.5.3. See [UPGRADING](UPGRADING.md#to-2132).

### Fixed

- **Locale tabs heading styles**: `.nowo-form-kit-locale-tabs__title` and `__help` had no size or colour (hosts added
  their own CSS). `locale-tabs.css` now sets the title size (`0.875rem`, weight 600), a smaller muted help
  (`--nowo-ui-help-text` / `--nowo-ui-muted`, neutral fallback) and the label gap between heading and tabs
  (`--nowo-ui-form-label-gap`, fallback `0.5rem`); tunable with `--nfk-locale-tabs-title-size`,
  `--nfk-locale-tabs-help-size`, `--nfk-locale-tabs-label-gap`. With the UiKit renderer the help also gets the UiKit
  `muted` slot (unless `help_class` is passed), so its colour follows the theme without the stylesheet.

### Changed

- `nowo-tech/ui-kit-bundle` (require-dev / suggest) is now `^2.5.3` (locale tab list without the `tabs` margin,
  kebab `<button>` items).
- `.php-cs-fixer.dist.php`: `new ParallelConfig(2)` (REQ-CS-001).

## [2.13.1] - 2026-10-19

Locale tabs form_row options merged over the type option, TranslatableMapType default_locale fallback, UiKit 2.5.2. See [UPGRADING](UPGRADING.md#to-2131).

### Fixed

- **Locale tabs row options**: `form_row(field, {locale_tabs: {...}})` options are now merged over the form-type
  `locale_tabs` option (the row variables win); the theme used to read `form.vars.locale_tabs` only and ignore them.
- **`TranslatableMapType` `default_locale`**: a value that is not among `locales` falls back like an unset one (the
  configured default locale when listed, else the first locale), so the default tab no longer goes missing.

### Changed

- `nowo-tech/ui-kit-bundle` (require-dev / suggest) is now `^2.5.2`: language names without ext-intl
  (polyfill-intl-icu) and legends hidden by the tabs runtime itself.

### Documentation

- USAGE: entries mode needs the translation collection keyed by locale (Doctrine `indexBy: 'locale'`, also for
  `mapped: false` collections) for entity-level `Assert\Valid` errors to land inside their tab; row-variable merge.

## [2.13.0] - 2026-10-19

Locale tabs for translation entities, field_<locale> groups and TranslatableMapType, rendered through UiKit 2.5; checkbox_row profile chrome and profile help_attr. See [UPGRADING](UPGRADING.md#to-2130).

### Added

- **Locale tabs, three shapes** (`locale_tabs` option and `_locale_tabs.html.twig`): `entries` — a list of translation
  entities (CollectionType whose entries have a `locale` child: the locale becomes a hidden input, the other children
  fill the panel, entry errors inside it); `fields` — `field_<locale>` groups (`fields: [...]`,
  `pattern: '{field}_{locale}'`, only those children are rendered); `children` — the 2.7 shape. `mode` is detected.
- **`TranslatableMapType`**: a JSON `{locale: text}` map as one field per locale with tabs on by default
  (`locales`, `default_locale`, `required_locales` → required + NotBlank, `entry_type`, `entry_options`,
  `remove_empty`, `input: array|json`); unknown locales in the map are kept on submit.
- **UiKit renderer**: with nowo-tech/ui-kit-bundle **^2.5** installed (suggested, not required) the tabs render
  through UiKit's `locale_tabs` partial (no-JS stacked fieldsets, WAI-ARIA keyboard, browser validation opens the
  hidden panel, `nowo-ui:tabs:change`, Live Component safe); `ui_kit: false` or
  `nowo_form_kit.locale_tabs.renderer: native` keeps the neutral markup. Twig functions
  `nowo_form_kit_locale_tabs()` / `nowo_form_kit_ui_kit_locale_tabs()` (`LocaleTabsTwigExtension`).
- `required_locales` (marker `*`, distinct from the default `★`), `aria-invalid="true"` on invalid widgets,
  screen-reader text for default / required / invalid tabs, translated tab list label (`locale_tabs.*` in
  `NowoFormKitBundle` for en, es, de, fr, it, nl, pt), `lang` / `dir` per panel, roving `tabindex`.
- Configuration `nowo_form_kit.locale_tabs` (`locales`, `default_locale` from the framework by default, `renderer`,
  `form_theme`); the locale tabs form theme is registered in `twig.form_themes` automatically.
- Profile-level **`help_attr`** (shorthand for `defaults.help_attr`) and **`css_framework`**.

### Changed

- `static_blocks.html.twig` `checkbox_row`: the Bootstrap chrome (`mb-3` fallback, `form-check`,
  `form-check-input`, `form-check-label`) only when the default profile's framework (profile `css_framework`, else
  the root `css_framework`) is Bootstrap; otherwise the row uses the profile's `defaults.row_attr.class`
  (`CheckboxRowExtension`). Unchanged for the default `css_framework: bootstrap`.
- Locale tabs: default marker `★` (was `*`, now the required marker), screen-reader text `(Spanish, default
  language)` instead of ` — Spanish`, `_locale_tabs.html.twig` needs the bundle's Twig extension (registered
  automatically).

## [2.12.0] - 2026-10-13

### Added

- **Opt-in combobox Stimulus peer** (`src/Resources/assets/stimulus-peers/combobox_controller.ts`, identifier `combobox`): searchable picker over static server-rendered options that writes the chosen `data-value` into a (hidden) input. WAI-ARIA 1.2 editable combobox with list autocomplete (fills missing `role` / `aria-expanded` / `aria-controls` / `aria-autocomplete` / `aria-selected` / ids, `aria-activedescendant` for the active option), keyboard support (`ArrowDown` / `ArrowUp` with wrap, `Alt+ArrowDown`, `Enter`, `Escape`, `Tab`), close on outside pointer, `combobox:change` event. Copy is neutral and configurable: `requiredMessage` value → query `data-required-message` → `"Please select an option."`. Values `open`, `requiredMessage`, `selectedClass`, `activeClass`; targets `query`, `value`, `list`, `option`, `empty`. Not built or registered by the bundle — hosts import it into their own Stimulus app. Ported from Symfony Beacon (spec 138 FR-010); fixes the host version's list reopening after a selection and the required message being cleared by the follow-up focus. See [Usage — Combobox](USAGE.md#combobox-stimulus-peer-optional) and [`specs/003-combobox-peer`](../specs/003-combobox-peer/spec.md).

### Changed

- Tooling: `@hotwired/stimulus` added as a **dev** dependency (Vitest / `tsc` only); Vitest and `tsconfig.json` now include `src/Resources/assets/stimulus-peers/` (coverage gate 100%).

## [2.11.0] - 2026-10-13

### Added

- **`csrf_action_form()` Twig function** (`Nowo\FormKitBundle\Twig\CsrfActionFormExtension`): `csrf_action_form(action, csrfTokenId, method = 'POST', named = true, csrfFieldName = '_token', fields = {}, fieldTypes = {}, fieldOptions = {})` returns a `FormView` built on `CsrfOnlyFormFactory` — nested `csrf_only[_token]` (`named: true`, default), flat token (`named: false`, e.g. kit `csrfFieldName: '_csrf_token'`) or CSRF + flat typed fields (`fields`, always empty prefix). The method is upper-cased. Registered (tag `twig.extension`) only when Twig is installed and the new `twig.csrf_action_form` option is `true` (default). See [CSRF.md](CSRF.md#twig-function-csrf_action_form).
- **Config `nowo_form_kit.twig.csrf_action_form`** (`bool`, default `true`). Twig does not throw on duplicate function names (the extension registered last wins), so hosts that still ship their own `csrf_action_form` keep working; set `false` to keep only the host one explicitly.

### Changed

- Maintenance: `nowo-tech/phpstan-frankenphp` dev lock bumped to 1.3.0; Rector type-control cleanups in `FormOptionsMerger` (no behaviour change).

## [2.10.0] - 2026-10-13

### Added

- **`convention_requires_translation`** (opt-in, per profile + root-level global default, default `false`): when `true`, `FormOptionsMerger` applies the auto help / placeholder convention keys (`{form}.{field}.help` / `.placeholder`) only if the key exists in the translator catalogue (current locale, fallback locales included) for the field's translation domain. Labels are never affected and explicit / configured values still win. The translator is injected as an optional dependency (`@?translator`); without a `TranslatorBagInterface` translator the flag has no effect. See [Configuration](CONFIGURATION.md#convention-keys-only-when-translated-convention_requires_translation).

## [2.9.0] - 2026-10-13

### Fixed

- **Explicit placeholders win over the convention key:** a root `placeholder` passed in PHP options (or set in profile `defaults`, `field_types`, `by_form`) is now applied to `attr.placeholder` even when `auto_placeholder` is on. Previously the `{form}.{field}.placeholder` key was already in `attr`, so the explicit value was silently ignored.
- **ChoiceType placeholder semantics:** for `ChoiceType` and its subtypes (`EntityType`, `EnumType`, `CountryType`, UX Autocomplete, custom types whose parent chain reaches `ChoiceType`, detected through the form registry / `type_map`), the root `placeholder` is kept as the empty-option label instead of being moved to `attr.placeholder`, and `placeholder: false` is preserved (no empty option; previously it was dropped and Symfony fell back to an empty option / "None" radio). Choice fields no longer get the convention key in `attr.placeholder`; the generic profile / `by_form` `defaults.placeholder` is not applied to them.
- **No convention placeholder on checkbox / radio / hidden fields** (and subtypes): `auto_placeholder` no longer adds a meaningless `attr.placeholder` key to them.

## [2.8.1] - 2026-10-13

### Fixed

- **`MoneyModelTransformer` with `money_scale: 0`:** submitted decimals are now rounded half up (`"12.6"` → `13`) like every other scale and like float model values, instead of being truncated (`"12.6"` → `12`).

### Changed

- **Docs:** `docs/CONFIGURATION.md` documents the root `css_framework` key (what `CssClassUtilities` does per framework) and the money transformer semantics (`int` / integer string = cents, `float` / decimal string = amount, half-up rounding).
- **Dev:** Vitest 4 (`vitest` / `@vitest/coverage-v8` `^4.1.11`), jsdom 30; extra help-modal test for the branches Vitest 4 coverage now counts.

## [2.8.0] - 2026-10-12

### Added

- **Accessible names for unlabelled selects (on by default):** `UnlabelledChoiceAriaLabelExtension` (extends `ChoiceType`, so also `EntityType`, `EnumType`, `CountryType`, …). When a non-expanded choice field renders with `label: false` and has no `aria-label` / `aria-labelledby`, it sets `aria-label` from the translated `help`, else the translated `placeholder` (`translation_domain: false` keeps the raw text). Fixes compact filter bars (e.g. the built-in `filter` profile) whose selects were announced as "combo box, Any level". Toggle with `nowo_form_kit.accessibility.unlabelled_choice_aria_label` (default `true`); the service is only registered when enabled. Ported from symfony-beacon `UnlabelledChoiceAriaLabelExtension`.
- Container parameter `nowo_form_kit.accessibility.unlabelled_choice_aria_label`.

## [2.7.1] - 2026-10-09

### Fixed

- **`stateless_csrf` (2.7.0) broke every targeted form**: Symfony's DI form extension validates each type extension against its static `getExtendedTypes()`, which returned `[]`, so rendering e.g. the cookie-consent form threw *"The extended type … does not match any of the actual extended types"*. The configured `form_types` are now published at boot (`NowoFormKitBundle::boot()` → `StatelessCsrfTokenIdExtension::configureExtendedTypes()`); regression test with the real `DependencyInjectionExtension`.

### Changed

- Development: `composer.json` pins `config.platform.php` to 8.2.0 so the committed lock stays installable on the minimum PHP; CI overrides the platform per matrix cell.

## [2.7.0] - 2026-10-09

### Added

- **Per-locale tabs:** form theme `@NowoFormKitBundle/form/locale_tabs_theme.html.twig`, partial `@NowoFormKitBundle/form/_locale_tabs.html.twig` and `LocaleTabsExtension` (`locale_tabs` option on any field). One WAI-ARIA tab per locale for a compound field keyed by locale; default locale marked, invalid tabs marked and opened first, inactive panels `hidden` server-side; works with a Stimulus `tabs` controller (UiKit peer) or the UiKit `nowo-ui-tabs.js` IIFE; no inline JS/CSS. Optional neutral stylesheet `locale-tabs.css` (`nowo_form_kit` asset package). Ported from podologiapriego-web `admin/_locale_field_tabs.html.twig`.
- **Stateless CSRF for public forms (opt-in):** `nowo_form_kit.stateless_csrf` (`enabled: false`, `token_id: submit`, `form_types: []`, `register_stateless_token_id: true`) → `StatelessCsrfTokenIdExtension` per listed type + `framework.csrf_protection.stateless_token_ids` prepend. Ported from podologiapriego-web `StatelessCsrfPublicFormTypeExtension`.
- Dev dependency `symfony/security-csrf` (integration test of the CSRF token id default).

## [2.6.2] - 2026-10-09

### Fixed

- **`symfony/asset` is now a hard requirement** (`^7.4 || ^8.0`). `FormKitExtension::prepend()` registers the `nowo_form_kit` package under `framework.assets` whenever FrameworkBundle is enabled; applications without `symfony/asset` failed to boot because FrameworkBundle rejects asset configuration when the component is missing.

### Dependencies

- Dev lock: `symfony/asset` 7.4.8. Demo `symfony8` lock refreshed for the new bundle requirement.

[2.15.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.15.0
[2.14.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.14.1
[2.14.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.14.0
[2.13.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.13.3
[2.13.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.13.2
[2.13.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.13.1
[2.13.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.13.0
[2.12.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.12.0
[2.11.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.11.0
[2.10.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.10.0
[2.9.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.9.0
[2.8.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.8.1
[2.8.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.8.0
[2.7.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.7.1
[2.7.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.7.0
[2.6.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.6.2

## [2.6.1] - 2026-10-09

### Dependencies

- Dev lock refreshed: Symfony 7.4.20, Twig 3.30.0, PHPStan 2.3.1, `phpstan/phpstan-symfony` 2.1.0, Rector 2.7.0, `igor-php/igor-php` 0.10.1.
- Demo `symfony8`: Symfony 8.1.8, Twig 3.30.0 and the latest nowo-tech form widget bundles (`otp-input-bundle` 1.6.1, `tag-input-bundle` 1.3.0, `ckeditor5-editor-bundle` 1.4.12, …).

[2.6.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.6.1

## [2.6.0] - 2026-10-07

### Added

- **`FormKitAbstractType::twigOwnedChromeOptions()`** (protected): returns `label`, `help`, `placeholder` and `translation_domain` all set to `false` for fields whose visible copy is rendered by Twig.
- **`FormKitAbstractType::addChoiceWithFormPlaceholder()`** (protected): adds a `ChoiceType` through the FormKit merge pipeline using the builder bound by `withBuilder()`, then restores the ChoiceType empty option (convention key `{block_prefix}.{field_snake}.placeholder`, custom key, or skipped with `placeholder => false`).
- Unit tests and `ExposedFormKitAbstractType` test double for both helpers.

[2.6.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.6.0

## [2.5.4] - 2026-09-27

### Added

- **REQ-CS-008:** `igor-php/igor-php` (require-dev only), root `igor.json`, Composer/`Makefile` `igor` target, and `release-check` wiring for FrankenPHP worker-state audit.

### Changed

- **Worker safety (Igor):** justified `// @igor-ignore` annotations and/or `ResetInterface` / request-scoped fixes so `make igor` passes on package `src/`.

[2.5.4]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.5.4

## [2.5.3] - 2026-09-24

### Fixed

- **FrankenPHP worker (`FRANKENPHP_RESET_KERNEL` unset/false):** `MultiStepWizardSession` resolves the session from `RequestStack` on every access instead of pinning the first request’s session in the constructor.
- `FormKitControllerTrait` now rejects a `type_map` alias that does not resolve to a form type class with the same `InvalidArgumentException` as an unknown alias.
- PHPStan level 8 clean with `FormKitControllerTrait` and `tests/` analysed (optional A2lix type remains excluded).

### Changed

- Composer lock: Symfony Form / PropertyInfo **7.4.18 → 7.4.19** (and related lock refresh).

### Documentation

- Added [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) (kernel reuse / no reset); USAGE + README + DEMO-FRANKENPHP worker guidance; baseline spec `FR-WORKER-*` / `FR-WIZ-002`.

### Notes

- **No breaking API changes.** Trait setters remain DI/constructor configuration (documented for shared services under worker).

[2.5.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.5.3

## [2.5.2] - 2026-08-25

### Fixed

- **`static_blocks` `form_label_content`** — Guard `required is defined` before appending `required_label_suffix`. Symfony Submit/Button/Reset widgets call this block without `required`; Twig `strict_variables` (debug) threw `RuntimeError`.

### Notes

- **No API or configuration changes** for integrators unless noted above.

[2.5.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.5.2

## [2.5.1] - 2026-08-24

### Fixed

- **Demo `/nowo-special-fields`** — Asset Mapper 404s: recompile `public/assets` on Docker/Makefile `up`/`install`; stop importing Select All controller from `vendor/` in `stimulus_bootstrap.ts`; load Nowo widget CSS/JS via `stylesheets` / `page_scripts` blocks **after** the Stimulus importmap; use named asset packages where required.
- **Demo slide-to-confirm** — Custom form theme hides the native Bootstrap checkbox beside the slider (`SlideToConfirmType` extends `CheckboxType`).
- **Demo translations (EN, ES, FR, DE)** — PhoneInput and SlideToConfirm gate-profile keys in domain `messages` (`Country prefix`, `form.slide_to_unlock`, etc.).

### Notes

- **No API or configuration changes** for integrators unless noted above.

[2.5.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.5.1

## [2.5.0] - 2026-08-24

### Added

- **Optional nowo-tech form widgets** — Composer **suggest** + `FormTypeMap` entries + helpers on `FormOptionsTrait` / `FormKitTrait` / `FormKitControllerTrait` (throw `LogicException` if the package is missing): OTP, phone, password toggle/strength, icon selector, CKEditor 5, Tiptap, tag input, slide-to-confirm. Distinct from Symfony `addPassword()` and FOS `addCKEditorField()` (CKEditor 4).
- **Demo `/nowo-special-fields`** — uses the new helpers; adds **TagInputBundle** and **SlideToConfirmBundle**.

### Changed

- **demo/symfony8** — lockfile includes `nowo-tech/tag-input-bundle` and `nowo-tech/slide-to-confirm-bundle`; Live Component routes use prefix `/{_locale}/_components` so they do not swallow locale demo pages.

[2.5.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.5.0

## [2.4.5] - 2026-08-24

### Changed

- **QA:** add `phpstan-frankenphp` extension (REQ-CS-005).
- **Docs:** PHP-FIG PSR evaluation (REQ-CS-007).

### Notes

- **No API or configuration changes** for integrators unless noted above.

[2.4.5]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.5

## [2.4.4] - 2026-08-19

### Changed

- **Maintainer:** demo reference PHP CS Fixer alignment.

[2.4.4]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.4

## [2.4.3] - 2026-08-19

### Added

- **`FormKitExtension::prepend()`:** registers a built-in `filter` profile when the host omits `nowo_form_kit.profiles.filter`, so `AbstractGetFilterType` resolves in apps without a custom filter profile.

### Security

- **CI:** run `composer audit --locked` after dependency install (REQ-SEC / P3).

[2.4.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.3

## [2.4.2] - 2026-08-18

### Changed

- **Demos:** pin `nowo-tech/hot-reload-bundle` to `^1.4` with FrankenPHP Mercure/`hot_reload` (`dev`/`test` only).

[2.4.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.2

## [2.4.1] - 2026-08-17

### Fixed

- **Submit/Button/Reset:** `FormOptionsMerger` no longer passes `help` / `help_attr` (and other FormType-only options) to Symfony button types. FormKit 2.3.0 always injected `help_attr` from profile defaults, which broke hosts using `addWithDefaults(..., SubmitType::class)` (OptionsResolver: option `help_attr` does not exist).

[2.4.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.1

## [2.4.0] - 2026-08-15

### Added

- **CSRF-only forms:** `CsrfOnlyType`, `HiddenFieldsCsrfType`, and `CsrfOnlyFormFactory` (`create` / `createNamed` / `createWithFields`) for single-action POSTs with flat or nested CSRF tokens and optional typed fields.
- **GET filters:** `AbstractGetFilterType` (profile `filter`, CSRF off, method GET), `GetFilterFormFactory` (rootless form name), and `SearchQueryType` (`q` search field).
- Built-in FormTypeMap entry **`search`** → Symfony `SearchType`.
- Documentation: [docs/CSRF.md](CSRF.md).

[2.4.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.4.0

## [2.3.0] - 2026-08-14

### Added

- Profile / `by_form` **`defaults.label`**, **`defaults.placeholder`**, **`defaults.help`**, and **`defaults.required`**: optional scalar defaults applied to every field (same cascade as `attr` / `row_attr`). Example: filter profiles can set `label: false` and `required: false` once instead of repeating under each `field_types.*`.
- **`field_types.*.required`** and **`by_form.*.fields.*.required`**: declare a default `required` flag in YAML (overridable by later layers / PHP options).
- Profile **`defaults.help_attr`** (and peers on `field_types` / `by_form`) for Symfony `help_attr` chrome.

[2.3.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.3.0

## [2.2.0] - 2026-08-05

### Added

- Profile options **`auto_placeholder`** and **`auto_help`** (default `true`): when `false`, FormOptionsMerger does not inject `{form}.{field}.placeholder` / `.help` convention keys (kits can set labels only without empty help keys in the UI).
- **`FormOptionsTrait::addWithDefaults()`** inherits the form-level `translation_domain` into field options when the field does not set one, so AuthKit/host form domains win over the profile default.

[2.2.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.2.0

## [2.1.0] - 2026-08-04

### Changed

- **PHPStan:** analyse `src/` only (align with UiKit); exclude unused `FormKitControllerTrait` from the scan path. Clear remaining `class-string` / `list` typing for Form builder helpers.
- **PHPStan stubs:** `stubs/OptionalFormTypes.php` for optional UX / A2lix / CKEditor form type FQCNs (still Composer `suggest` only at runtime).
- Demo `test-coverage` aliases HTTP smoke (`make verify`) — the demo has no PHPUnit suite; healthchecks accept HTTP **2xx/3xx**.

### Added
- **REQ-TWIG-004:** require `twig/extra-bundle` + `twig/string-extra`; `make check-twig-extra` in `release-check`; demos register `TwigExtraBundle`.
- **Twig-CS-Fixer:** `vincentlanglet/twig-cs-fixer`, `.twig-cs-fixer.php`, `composer twig:lint` / `twig:fix`.

[2.1.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.1.0

## [2.0.20] - 2026-08-04

### Changed

- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.17 → 3.95.18**; `rector/rector` **2.5.8 → 2.5.9**; `phpstan/phpstan` **2.2.6 → 2.2.7**; `nowo-tech/phpstan-frankenphp` **1.0.1 → 1.0.2**; GitHub Action `actions/stale` **10 → 11** (Dependabot).
- **demo/symfony8** — Nowo special-field widgets install from **Packagist** (`^1.0` / `^2.0`) instead of path repos under `/var/nowo-bundles`; removed that Compose volume; `prefer-stable: true`; refreshed `composer.lock` and `config/reference.php`.
- **demo/README** — Documents Packagist-based Nowo widget packages (no sibling-path mount required).

## [2.0.19] - 2026-07-29

### Changed

- **Makefiles (REQ-MAKE-009 / REQ-MAKE-010)** — Prefer Docker Compose V2 (`docker compose`) with V1 fallback; optional `-include` of monorepo `update-deps` helpers so standalone CI checkouts do not fail when `../.scripts/` is absent.
- **PHP CS Fixer** — Import/`use` cleanups after 2.0.18 (traits, transformers, tests); no behaviour change.

## [2.0.18] - 2026-07-29

### Added

- **`docs/PHPSTAN.md`** — PHPStan level 8 policy: empty baseline (`ignoreErrors: []`); fix findings in code/tests instead of silencing.
- **QA Make targets** — `coverage-check` (fail if Lines &lt; 99%, REQ-TEST-003), `check-open-prs` (REQ-REL-003), `demo-smoke` (REQ-TEST-011); wired into `release-check`.
- **Scripts** — `.scripts/coverage-fail-under.sh`, `.scripts/check-open-prs.sh`.
- **PHPUnit / CI** — `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` (REQ-SF-005) in `phpunit.xml.dist` and CI test jobs.
- **PHPUnit** — Broader coverage for traits, controller trait, constraint factory, TwigPathsPass, FormKitExtension.

### Changed

- **PHPStan** — Cleared `phpstan-baseline.neon` suppressions; PHPDoc and small type-safe cleanups across `src/` (traits, mergers, CSS utilities, constraints, static types).
- **Docs** — Table of contents on CHANGELOG, UPGRADING, INSTALLATION, CONFIGURATION, ROADMAP, SECURITY notes, GITHUB_CI, SPEC-DRIVEN-DEVELOPMENT; README coverage badge **99.4%**.
- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.15 → 3.95.17**; `rector/rector` **2.5.7 → 2.5.8**; `phpstan/phpstan` **2.2.5 → 2.2.6** (Dependabot).
- **Flex recipe stubs** — Comment URLs point to GitHub repo **FormKitBundle**.

### Fixed

- **`.github/SECURITY.md`** — Correct product name (Form Kit Bundle).
- **`ConstraintDefinitionFactory`** — Reject FQCNs that do not extend Symfony `Constraint`.

## [2.0.17] - 2026-07-26

### Added

- **REQ-ASSETS-004** — `FormKitExtension` implements `PrependExtensionInterface` and registers the `nowo_form_kit` asset package (`base_path: /bundles/nowoformkit`). Load help-modal assets with `asset('help-modal.js', 'nowo_form_kit')` / `asset('help-modal.css', 'nowo_form_kit')`.
- **REQ-DOCS-017** — FrankenPHP Friendly Worker Mode banner in README (`docs/images/frankenphp-friendly.png`).
- **REQ-CS-005** — `nowo-tech/phpstan-frankenphp` in `require-dev` with `ruleset-classic` + `ruleset-worker` in `phpstan.neon.dist`.
- **Help modal portal** — `help-modal.ts` MutationObserver keeps Form Kit help modals on `document.body` for dynamic/background forms; stale duplicates by id are removed on refresh.

### Changed

- **REQ-DEMO-010** — Symfony 8 demo uses `dunglas/frankenphp:1-php8.5-alpine`; `FRANKENPHP_MODE` (`classic` \| `worker`, default `worker`) in `.env.example` / Compose / entrypoint.
- **Docs / demo / recipe** — Help modal examples use the named asset package (no hard-coded `/bundles/nowoformkit/...` paths).
- **PHP CS Fixer** — `fully_qualified_strict_types.import_symbols` enabled; related `use` import cleanups in src/tests.

## [2.0.16] - 2026-07-22

### Added

- **`TwigPathsPass`** — Registers the Twig namespace **`NowoFormKitBundle`** and prepends `templates/bundles/NowoFormKitBundle/` so application overrides win.
- **PHPUnit** — Coverage for `TwigPathsPass` and bundle `build()` registration.

### Changed

- **Twig logical names** — Prefer `@NowoFormKitBundle/...` (was `@NowoFormKit/...`) for form themes, help-modal shells, and `form_renderer`. Docs, Flex recipe stub, demo, and Spec Kit baseline updated. See [UPGRADING](UPGRADING.md).
- **composer.lock / demo lock** — Dependency refresh (PHPUnit, related packages).

## [2.0.15] - 2026-07-20

### Added

- **REQ-GIT-001** — No Cursor `Co-authored-by` trailers: `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh` / `strip-cursor-coauthor-from-history.sh`, Cursor rule, CI job **Git history (no Cursor co-author)**, Make targets `setup-hooks` / `check-no-cursor-coauthor` / `strip-cursor-coauthor-from-history`, and [docs/GITHUB_CI.md](GITHUB_CI.md).
- **PHPUnit stubs** — `DummyFormTypeWithTwoParams` / `DummyFormTypeWithWrongParamType` cover compiler-pass skips for invalid `setFormOptionsMerger` signatures.
- **CSS utility tests** — Empty class-token cases for Bootstrap and Foundation `orderClasses()`.

### Changed

- **CONTRIBUTING / RELEASE / README** — Document git hooks and link to GitHub CI requirements.
- **`.gitignore`** — Ignore `.cursor/sandbox.json`.

## [2.0.14] - 2026-07-20

### Added

- **`CODE_OF_CONDUCT.md`** — Contributor Covenant 2.1; linked from README and [Contributing](CONTRIBUTING.md).

## [2.0.13] - 2026-07-20

### Changed

- **demo/symfony8** — Refreshed `composer.lock` (Guzzle PSR-7, Select All Choice, Twig Inspector, OTP path package branch) and regenerated `config/reference.php` for `default_profile` / `profiles`, `by_form`, and `constraint_message_convention`.
- **demo translations (EN/ES/FR/DE)** — Choice-fields copy no longer mentions removed Symfony 7 demo (“this demo” only).
- **demo/Makefile** — Comment aligned with symfony8-only targets.

## [2.0.12] - 2026-07-20

### Changed

- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.11 → 3.95.15**; `rector/rector` **2.5.3 → 2.5.7** (Dependabot; no runtime API change).

## [2.0.11] - 2026-07-18

### Changed

- **Config naming (AuditKit-style)** — `default_config` / `configs` renamed to `default_profile` / `profiles`. Legacy YAML keys and container parameters (`nowo_form_kit.default_config`, `nowo_form_kit.configs`) remain accepted during transition. See [UPGRADING](UPGRADING.md).
- **Docs — usage strategies** — Named strategies (**Options**, **Kit**, **Controller**, **Wrapped**) and techniques (**bound-builder**, **array-build**, **named-config**, **direct-merge**, **resolve-field-options**) in [USAGE](USAGE.md#usage-strategies); README / ROADMAP / INSTALLATION / recipes / demo YAML aligned with `profiles`.

## [2.0.10] - 2026-07-18

### Added

- **`by_form` config** — Per-form `defaults` / `fields` keyed by form name (block prefix), merged after `field_types` and before PHP options.
- **`constraint_message_convention`** — Opt-in: constraints without an explicit message get `{form}.{field}.constraints.{Name}` (and Length-style `.min` / `.max` message keys).
- **FormTypeMap built-ins** — Extended snake_case map: `date`, `datetime`, `money`, `collection`, `tel`, `file`, `submit`, and other core Symfony form types.
- **`#[FormKitConfig('name')]`** — PHP attribute on form types (`FormOptionsTrait` / `FormKitTrait`) to select a named config; `setFormKitConfigName()` still overrides.
- **Optional helpers** — `addDropzone()` / `addDropzoneField()` / `addDropzoneFieldType()` (`symfony/ux-dropzone`) and `addCropper()` / `addCropperField()` / `addCropperFieldType()` (`symfony/ux-cropperjs`) on FormOptionsTrait, FormKitTrait, and FormKitControllerTrait (`class_exists` guards).
- **Flex recipe stub 2.0** — [`.symfony/recipe/nowo-tech/form-kit-bundle/2.0/`](../.symfony/recipe/nowo-tech/form-kit-bundle/2.0/) with `nowo_form_kit.yaml`, Twig `static_blocks` form theme, and [docs/RECIPE.md](RECIPE.md) for recipes-contrib submission.
- **Demo Live Component** — `/{locale}/conditional-fields-live` (`ConditionalFieldsLive` + `ComponentWithFormTrait`) using the same FormEvents conditional form.
- **Spec Kit** — Baseline updated for `by_form`, constraint message convention, and `#[FormKitConfig]` (`US-02b` / `US-07`, `FR-FORM-011`).

### Changed

- **ROADMAP / INSTALLATION / USAGE / CONFIGURATION / README** — Aligned with symfony8-only demo, `by_form` / constraint convention, expanded type map, Dropzone/Cropper helpers, recipe docs, Live demo, layout examples (grid / floating labels), and `#[FormKitConfig]`.

## [2.0.9] - 2026-07-18

### Removed

- **demo/symfony7** — Symfony 7 demo removed from the repository. Use **demo/symfony8** (PHP 8.4+). Does not affect the Composer package (`demo/` is excluded from the archive). Bundle runtime still supports Symfony **7.4+**.

### Changed

- **Demo `/nowo-special-fields`** — Password examples: toggle only, strength only (`use_password_toggle => false`), and **combined** toggle + strength (`PasswordStrengthType` + `use_password_toggle => true`).
- **Make / docs** — Root and demo Makefiles, README, and DEMO-FRANKENPHP target **symfony8** only.

## [2.0.8] - 2026-07-18

### Added

- **`withBuilder()` + `add*Field()`** on `FormOptionsTrait` and `FormKitTrait` — bind the form builder once and add fields without repeating `$builder` (e.g. `$this->addTextField('name')`). Also: `boundBuilder()`, `addTypedField()` / `addNamedField()`, `buildFieldsFromArray()`, and bound aliases for choice presets and model-transformer helpers. Existing `addText($builder, …)` APIs remain unchanged.
- **`resolveFieldOptions()`** on `FormOptionsTrait` — merge conventions/config without adding the field (for `FormEvents` + `$form->add()`).
- **Docs + demo: conditional fields** — [USAGE](USAGE.md) patterns (build-time `if`, `PRE_SET_DATA` / `PRE_SUBMIT`, UI hide, Live Component) and demo route `/conditional-fields` (`ConditionalFieldsDemoType` + `BuildTimeConditionalDemoType`).
- **Demo coverage** — `/kit-api-patterns` (`FormKitAbstractType` snake_case + named `bootstrap` config); expanded `/controller-form` (select, radios, money); `boundBuilder()` on autocomplete demo; `configs.bootstrap` enabled in demo YAML.
- **Spec Kit baseline** — [`specs/001-baseline/`](../specs/001-baseline/) updated for bound-builder helpers, `resolveFieldOptions`, and conditional-field patterns (`US-03b`/`US-03c`/`US-06`, `FR-FORM-009`/`FR-FORM-010`, `SC-004`).

### Changed

- **README / INSTALLATION / USAGE** — Prefer `withBuilder()` + `add*Field()` in quick-start examples; document conditional-field patterns and new demo routes.

## [2.0.7] - 2026-07-09

### Fixed

- **demo/symfony7** — `composer.lock` resolved Symfony components to **7.4.x** again (lock had drifted to **8.2.x-dev** branches incompatible with the Symfony 7 demo platform).

### Changed

- **Demos (symfony7 / symfony8)** — Refreshed `composer.lock` (Guzzle, Intervention Image, Nowo path bundles, Symfony patches) and regenerated `config/reference.php` without `declare(strict_types=1)` to match Symfony Flex auto-generated config reference files.

## [2.0.6] - 2026-07-09

### Added

- **GitHub Spec Kit** — Baseline spec workflow for maintainers: [`.specify/`](../.specify/), Cursor Agent skills (`.cursor/skills/speckit-*`), and [`specs/001-baseline/`](../specs/001-baseline/) (`spec.md`, `code-inventory.md` covering **100%** of production code under `src/`).
- **`docs/SPEC-KIT.md`** — Operator manual: Specify CLI install, `specify init`, folder layout, Cursor `/speckit-*` skills, and maintainer checklist.

### Changed

- **`docs/SPEC-DRIVEN-DEVELOPMENT.md`** — Three-layer model (Spec Kit baseline, product behavior, `REQ-*` anchors); user stories aligned with Form Kit integrator goals; workflow step to keep Spec Kit artifacts in sync when `src/` changes.
- **README** — Link to **GitHub Spec Kit** documentation.
- **demo/symfony8** — `config/reference.php` synced via CI PHP CS Fixer.

## [2.0.5] - 2026-07-03

### Fixed

- **`static_blocks.html.twig`** — Use Twig `{% use %}` instead of `{% extends form_div_layout %}` so only bundle-defined blocks are registered. When this theme was listed after Bootstrap 5, inherited `form_div` radio/checkbox widgets overrode Bootstrap 5 and broke expanded choice fields (missing labels / `form-check` markup).
- **`FormOptionsTrait`** — `addChoiceRadios()` and `addChoiceCheckboxes()` disable `placeholder` and clear default `attr.class` (`form-control`) on the widget root so expanded choices and checkbox groups render correctly with Bootstrap 5.

### Changed

- **Demos (symfony7 / symfony8)** — **Choice fields** and **Nowo special fields** layout: `twig.form_themes` order (`static_blocks` and `@NowoPhoneInputBundle/Form/phone_input_widget.html.twig` before Bootstrap 5), `field_types` for `checkbox` / `choice`, demo CSS (OTP horizontal layout, password-strength markers), password-toggle CSS, icon-selector form theme workaround, and widget translations (password-strength labels, editor placeholders).
- **demo Makefiles** — Consistent `DOCKER_COMPOSE` / `SHELL` usage (fixes `docker: Permission denied` when `COMPOSE` env collides with the Make variable).

### Documentation

- **USAGE** — Form theme ordering with Bootstrap 5 and expanded choice fields.
- **UPGRADING** — Notes for 2.0.5.
- **demo/README** — Twig form themes and Nowo widget assets for special-fields demo.

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

[2.0.20]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.20
[2.0.19]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.19
[2.0.18]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.18
[2.0.17]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.17
[2.0.16]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.16
[2.0.15]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.15
[2.0.14]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.14
[2.0.13]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.13
[2.0.12]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.12
[2.0.11]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.11
[2.0.10]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.10
[2.0.9]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.9
[2.0.8]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.8
[2.0.7]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.7
[2.0.6]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.6
[2.0.5]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.5
[2.0.4]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.4
[2.0.3]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.3
[2.0.2]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.2
[2.0.1]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.1
[2.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v2.0.0
[1.0.0]: https://github.com/nowo-tech/FormKitBundle/releases/tag/v1.0.0
