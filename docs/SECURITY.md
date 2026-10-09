# Security

If you discover a security-related issue, please report it privately (e.g. by email to the maintainers) rather than opening a public issue. We will address it as soon as possible.

This bundle does not process user credentials or sensitive data itself; it only merges form options and translation keys. Security of your application remains your responsibility (CSRF, validation, etc.).

## Help modal HTML (frontend)

`help-modal.js` may set `innerHTML` for `title_html`, `content`, and `icon_html` from **developer-controlled** form/YAML options (not end-user input). Treat those fields as trusted markup: do not pass unsanitized visitor content. Prefer plain `title` / escaped text when the value is not intentionally HTML. Integrators using a strict CSP should allow the bundled script (and optional CSS) from the `nowo_form_kit` asset package. With nonce-based CSP, pass the per-request nonce stored in the request attribute `csp_nonce`: `<script src="{{ asset('help-modal.js', 'nowo_form_kit') }}" defer nonce="{{ app.request.attributes.get('csp_nonce') }}"></script>`.

## Stateless CSRF (`stateless_csrf`, opt-in)

Listed form types validate CSRF with Symfony's stateless manager (Origin/Referer + double-submit cookie) instead of a session token. Only list **anonymous** forms; keep authenticated / privileged forms on session-bound token ids. Behind proxies that strip `Origin`/`Referer`, ship the recipe's `csrf_protection_controller.js`, otherwise submissions are rejected (fail-closed).

## Locale tabs (Twig)

`_locale_tabs.html.twig` renders only auto-escaped variables and Symfony `form_row()` output; it emits no inline `<script>`, `style=` or `on*=` attributes (strict CSP). Titles/labels passed as options are escaped like any Twig variable.

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README where applicable. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files. |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled. |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Routes and admin features documented; roles configured for production. |
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |

Record confirmation in the release PR or tag notes.

