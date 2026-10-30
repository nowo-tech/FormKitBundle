# Security

Technical security guide of **FormKitBundle** (`nowo-tech/form-kit-bundle`): attack surface, threat model, mitigations and the release checklist. The short public policy (supported versions, private reporting) is [`.github/SECURITY.md`](../.github/SECURITY.md) — report vulnerabilities privately to **hectorfranco@nowo.tech**, never in a public issue.

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threat model](#threat-model)
- [Mitigations](#mitigations)
  - [Help modal HTML (frontend)](#help-modal-html-frontend)
  - [CSRF action forms (fail closed)](#csrf-action-forms-fail-closed)
  - [Input group prefix / suffix](#input-group-prefix--suffix)
  - [Stateless CSRF (`stateless_csrf`, opt-in)](#stateless-csrf-stateless_csrf-opt-in)
  - [Locale tabs (Twig)](#locale-tabs-twig)
  - [Static HTML and renderer buttons](#static-html-and-renderer-buttons)
  - [Submitted data](#submitted-data)
  - [FrankenPHP worker mode](#frankenphp-worker-mode)
- [Secrets and cryptography](#secrets-and-cryptography)
- [Logging](#logging)
- [Dependencies and updates](#dependencies-and-updates)
- [Permissions and exposure](#permissions-and-exposure)
- [AI security audit (REQ-SEC-004)](#ai-security-audit-req-sec-004)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

FormKit is a **form-building library**: it merges form field options and translation keys, ships form types / type extensions, Twig themes and functions, a help-modal script and an opt-in combobox Stimulus peer. It has **no HTTP controllers or routes**, no Doctrine entities, no CLI commands, makes **no outbound HTTP requests** and runs **no subprocesses**.

**Covered here:** what the bundle code does with its inputs, and which defaults keep host forms safe.
**Not covered:** the host application's authentication, authorization, validation rules and controllers. Forms built with FormKit are still the host's forms: CSRF configuration, access control on the actions that handle them, and validation of submitted data remain the host's responsibility.

## Attack surface

| Input | Who controls it | Where it is used |
| --- | --- | --- |
| Bundle configuration (`nowo_form_kit.*`: profiles, `type_map`, `css_framework`, `stateless_csrf`, `locale_tabs`, `twig.csrf_action_form`, `accessibility`) | Host developer | Compiled into container parameters at build time |
| Form options passed by host code (`label`, `help`, `help_modal`, `input_group_prefix` / `_suffix`, `html` of `StaticHtmlType`, `required_label_suffix`, `form_buttons` of the form renderer) | Host developer | Rendered by the bundle Twig themes; some are printed as HTML (see [Threat model](#threat-model)) |
| Submitted form data (HTTP POST / GET of forms built on bundle types: CSRF action forms, GET filters, `TranslatableMapType`, multi-step wizard) | **End user (untrusted)** | Symfony Form component: data transformers, validation, CSRF check |
| Session data (`MultiStepWizardSession`, key `nowo_form_kit_wizard_<name>`) | Server-side, filled from validated step data | Read back between wizard steps |
| Page DOM read by `help-modal.js` (`label[data-nowo-help-modal]` inside a `<form>`, nonce `<meta>` in `<head>`) | Host templates (developer), possibly user HTML if the host renders it | Builds the modal content, only for payloads carrying the per-request nonce |
| `X-Nowo-Form-Kit-Help-Modal-Nonce` request header | Browser (sent by `help-modal.js` on Turbo fetches) | Reused as the help-modal nonce of a fragment response when well-formed (base64url, 22–128 chars) |
| Translation catalogues (`NowoFormKitBundle` domain, host `messages` / custom domains) | Host developer / translators | Labels, placeholders, help, locale-tab hints (auto-escaped) |

## Threat model

| Threat | Applies | Notes |
| --- | --- | --- |
| **XSS** through options printed as HTML | Yes | `help_modal` `title_html` / `content_html` / `icon_html`, `input_group_prefix` / `_suffix`, `StaticHtmlType` `html`, `required_label_suffix`, renderer `form_buttons` are **trusted developer markup** (`\|raw`). Passing visitor input there is an XSS. Everything else (labels, help, placeholders, locale tab titles, translated strings) is auto-escaped by Twig. |
| **DOM XSS** through the help modal | Yes (mitigated) | The script copies developer HTML options (`*_html`) with `innerHTML` only for payloads bound to the page by the per-request nonce (unreleased; see [Help modal HTML](#help-modal-html-frontend)); a `data-nowo-help-modal` label forged through sanitised user HTML, even inside a `<form>`, is ignored. `title` / `content` are inserted as text. |
| **CSRF** on single-action POSTs | Yes (mitigated) | `CsrfOnlyType`, `HiddenFieldsCsrfType`, `csrf_action_form()` fail closed when form CSRF is unavailable; `CsrfOnlyFormFactory` / `csrf_action_form()` accept only POST / PUT / PATCH / DELETE and non-empty token ids. |
| **CSRF** on anonymous forms with stateless tokens | Opt-in | `stateless_csrf` relies on Origin/Referer + double-submit cookie; only for anonymous forms. Symfony base/core types (`FormType`, …) are rejected in `form_types`. |
| **Injection / execution** | No | No SQL, shell, `eval`, template-from-string or dynamic includes from user data; Twig template names are fixed bundle paths or host-provided theme names. |
| **Authorization / privilege** | Host | No routes or controllers; the bundle never grants or checks roles. |
| **SSRF / outbound requests** | No | No HTTP client usage. |
| **Deserialization** | Low | `TranslatableMapType` / `JsonModelTransformer` decode JSON strings with `json_decode($json, true)` into plain arrays (no objects are instantiated); there is no `unserialize()` of user data. Session data is stored by Symfony's session handler. |
| **Path traversal** | No | No file system access from user input. |
| **Session fixation / data leakage between users** | Low | Wizard state is per session key; the session is resolved from `RequestStack` on every access, so FrankenPHP worker mode cannot leak one user's wizard into another request. |
| **DoS** | Low | Work is proportional to the form definition; `TranslatableMapType` only creates children for the configured locales and, with `remove_empty` (default), drops blank values from the submitted map. |

## Mitigations

### Help modal HTML (frontend)

`help-modal.js` sets `innerHTML` only for `title_html`, `content_html` and `icon_html`, which are **developer-controlled** form/YAML options (not end-user input): treat them as trusted markup and never pass unsanitized visitor content. `title` and `content` are plain text and are inserted with `textContent` (escape by default; `content` was HTML before the unreleased change, see [UPGRADING](UPGRADING.md)).

**Origin binding (unreleased).** Every payload carries a per-request nonce (`HelpModalNonce`, stored in the main request attributes). The page prints the same value with `{{ nowo_form_kit_help_modal_meta() }}` inside `<head>`; the script reads it from `document.head` only and ignores any `label[data-nowo-help-modal]` whose payload nonce differs (or when the meta is missing). Markup that reaches the page through sanitised user HTML (sanitizers such as DOMPurify keep `form`, `label` and `data-*`) cannot know the nonce, so a forged label is ignored even inside a `<form>`; labels must also sit inside a `<form>`. For fragments loaded later, the script sends the page nonce in `X-Nowo-Form-Kit-Help-Modal-Nonce` on same-origin Turbo fetches and the server reuses it; a Live Component morph keeps the last bound payload of an existing label. The script only moves modal roots it created itself to `document.body` (never page markup that copies the `data-nowo-formkit-help-modal` marker), and its console diagnostics are off unless `window.NOWO_FORM_KIT_DEBUG = true`.

Integrators using a strict CSP should allow the bundled script (and optional CSS) from the `nowo_form_kit` asset package. With nonce-based CSP, pass the per-request nonce stored in the request attribute `csp_nonce`: `<script src="{{ asset('help-modal.js', 'nowo_form_kit') }}" defer nonce="{{ app.request.attributes.get('csp_nonce') }}"></script>`.

### CSRF action forms (fail closed)

Threat: a cross-site POST that triggers a privileged single action (delete, revoke, toggle) of a host or bundle controller built on `CsrfOnlyType` / `HiddenFieldsCsrfType` / `CsrfOnlyFormFactory` / `csrf_action_form()`.

Both types declare their own `csrf_protection` and `csrf_token_id` defaults. When Symfony's form CSRF extension (`form.type_extension.csrf`) is absent — `symfony/security-csrf` not installed, `framework.csrf_protection: false` or `framework.form.csrf_protection: false` — those options would resolve without effect: no token field, and `isValid()` true for any POST. Since 2.14.0 `CsrfProtectionGuard` makes both types throw a `LogicException` at build time in that situation, so the action can neither be rendered nor submitted without CSRF validation. Only an explicit `'csrf_protection' => false` passed by the calling code disables the check (use it only for actions that are not state-changing).

Bundles that expose mutating admin UIs on top of these types should additionally refuse to compile the container without form CSRF (compiler pass checking `security.csrf.token_manager`, `form.type_extension.csrf` and `form.type_extension.csrf.enabled`).

### Input group prefix / suffix

`input_group_prefix` / `input_group_suffix` are printed as HTML (icons, `<i class="bi bi-at"></i>`). They are
developer-controlled form options: never pass user input there (escape it first, e.g. `htmlspecialchars()`).

### Stateless CSRF (`stateless_csrf`, opt-in)

Listed form types validate CSRF with Symfony's stateless manager (Origin/Referer + double-submit cookie) instead of a session token. Only list **anonymous** forms; keep authenticated / privileged forms on session-bound token ids. Behind proxies that strip `Origin`/`Referer`, ship the recipe's `csrf_protection_controller.js`, otherwise submissions are rejected (fail-closed).

### Locale tabs (Twig)

`_locale_tabs.html.twig` renders only auto-escaped variables and Symfony `form_row()` output; it emits no inline `<script>`, `style=` or `on*=` attributes (strict CSP). Titles/labels passed as options are escaped like any Twig variable.

### Static HTML and renderer buttons

`StaticHtmlType` (`html` option), the form renderer `form_buttons` string and `required_label_suffix` are printed unescaped by design (custom markup). Build them from literals or escaped values only; for user-provided text use `StaticAlertType` (`message` is translated and escaped) or a regular field.

### Submitted data

All submitted values go through the Symfony Form component: type-specific data transformers (`SwitchModelTransformer`, `JsonModelTransformer`, `BoolModelTransformer`, `MoneyModelTransformer`, `CsvModelTransformer`), the constraints declared in configuration (`ConstraintDefinitionFactory`) or on the host type, and the CSRF check when enabled. `TranslatableMapType` builds children only for the configured locales (keys outside that list are kept as they were in the model, never created from the request), adds `NotBlank` to required locales and drops blank values when `remove_empty` is true.

### FrankenPHP worker mode

Bundle services hold only compiled configuration and are stateless between requests (see [`FRANKENPHP-WORKER-AUDIT.md`](FRANKENPHP-WORKER-AUDIT.md)); PHPStan FrankenPHP rulesets and Igor run in `make release-check`.

## Secrets and cryptography

The bundle stores **no secrets** and implements **no cryptography**. CSRF tokens are generated and validated by Symfony's `security-csrf` token manager (session-bound by default, stateless manager when `stateless_csrf` is enabled); FormKit only chooses the token id. No keys, passwords or tokens are committed; demo `.env` files are git-ignored (`.env.example` holds placeholders only).

## Logging

The PHP code **does not log** (no PSR-3 logger, no `error_log`). Exceptions thrown by the bundle (`LogicException` for missing optional packages or missing form CSRF, `InvalidArgumentException` for invalid configuration) carry configuration names only, never submitted values or tokens. The browser logger of `help-modal.js` prints a "script loaded" line with the build id; its diagnostic messages about modal wiring are **off by default** and only enabled when the page sets `window.NOWO_FORM_KIT_DEBUG = true` before the script loads. It never logs field values or raw payloads (a parse error logs the payload length only). One fixed `console.warn` hint is printed when help labels are present but the nonce `<meta>` is missing.

## Dependencies and updates

- Runtime requirements are limited to Symfony components (`^7.4 || ^8.0`), `doctrine/collections`, `twig/extra-bundle` and `twig/string-extra`; optional widgets are Composer **suggest** only.
- CI runs `composer audit --locked` on every push / pull request (all matrix cells) and fails on reported advisories; Dependabot opens weekly Composer and GitHub Actions updates.
- Security fixes are released as patch versions on the supported line (see [`.github/SECURITY.md`](../.github/SECURITY.md)) and announced in [`CHANGELOG.md`](CHANGELOG.md) under **Security**, with a GitHub Security Advisory when a published version is affected.

## Permissions and exposure

FormKit exposes **no endpoints**: no routes, controllers, commands or public services meant to be called from HTTP. Public assets are the help-modal script/CSS and the locale-tabs CSS under `/bundles/nowoformkit` (asset package `nowo_form_kit`); with a strict CSP, allow them by path or nonce (see [Help modal HTML](#help-modal-html-frontend)). Access control of the actions that handle FormKit forms is the host's: protect them with `#[IsGranted]` / access_control as for any Symfony form.

## AI security audit (REQ-SEC-004)

| Field | Value |
| --- | --- |
| Date | 2026-10-30 |
| Version | v2.15.0 (`8bef3ab`) |
| Method | Claude Code static review (AI security audit of `src/`, Twig, `help-modal.ts`, configuration, packaging) |
| Grade | **Pass (good)** — overall risk **Low** |
| Recorded in | Nowo bundles monorepo `BUNDLES_SECURITY_ANALYSIS.md`, Appendix BH |

**Findings** (all **fixed in the next release**, see [CHANGELOG](CHANGELOG.md) `[Unreleased]` → Security):

| Id | Severity | Finding | Fix |
| --- | --- | --- | --- |
| L1 | Low | `help-modal.js` trusted any `label[data-nowo-help-modal]` inside a `<form>`; sanitised user HTML keeping `label` + `data-*` rendered in a host form reached `innerHTML` (`icon_html`, `title_html`, `content`) on init (stored XSS). | Per-request nonce in every payload checked against `<meta name="nowo-form-kit-help-modal-nonce">` in `<head>`; `title` / `content` escaped by default, `title_html` / `content_html` / `icon_html` opt-in HTML. |
| L2 | Low | The portal moved any element carrying `data-nowo-formkit-help-modal` to `document.body`. | Only roots created by the script (tracked in a `WeakSet`) are relocated. |
| I7 | Info | Browser logger had `alwaysLog: true` and logged the raw payload on parse errors (contradicting the [Logging](#logging) section). | Diagnostics off by default (`window.NOWO_FORM_KIT_DEBUG` opt-in); parse errors log the length only. |
| I8 | Info | No `.gitattributes`: Composer dist / `git archive` shipped `demo/` (demo `APP_SECRET`, port bound to `0.0.0.0`), tests, specs, `.scripts`, e2e and CI files. | `.gitattributes` `export-ignore` for demo, tests, specs, tooling and CI files. |
| I9 | Info | `CsrfOnlyFormFactory::create*()` / `csrf_action_form()` accepted GET and an empty CSRF token id. | Only POST / PUT / PATCH / DELETE are accepted (GET, HEAD, OPTIONS, TRACE, empty throw `InvalidArgumentException`), and only non-empty token ids. |
| I4 | Info | `stateless_csrf.form_types` accepted `FormType` and other base types (stateless token id on every form). | `FormType` and every type under `Symfony\Component\Form\` / `Symfony\Bridge\` are rejected at config time (`%env()%` entries at compile time). |

**Verified:** `CsrfProtectionGuard` makes `CsrfOnlyType` / `HiddenFieldsCsrfType` fail closed when form CSRF is unavailable; stateless CSRF token ids are opt-in (`stateless_csrf.enabled: false` by default); bundle Twig output is auto-escaped except the documented trusted-HTML options (`*_html` help-modal fields, `input_group_prefix` / `_suffix`, `StaticHtmlType` `html`, `required_label_suffix`, renderer `form_buttons`).

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document and [`.github/SECURITY.md`](../.github/SECURITY.md) are current (supported versions match the released line) and linked from the README. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files (GitGuardian check green). |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled; `\|raw` only on developer-controlled options listed in the [Threat model](#threat-model). |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Routes and admin features documented; roles configured for production. |
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |
| **AI security audit (REQ-SEC-004)** | The package **passes** the latest AI security audit; the latest recorded grade and findings are kept in the Nowo bundles monorepo `BUNDLES_SECURITY_ANALYSIS.md` (REQ-SEC-004 table). Re-run the audit when CSRF handling, HTML-printing options or the help-modal script change, and do not release on a failing grade. |

Record confirmation in the release PR or tag notes.
