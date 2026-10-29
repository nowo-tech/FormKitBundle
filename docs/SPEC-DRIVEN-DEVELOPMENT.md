# Spec-driven development

In this repository, **spec-driven development** has three layers that stay in sync:

1. **GitHub Spec Kit baseline** — [`specs/001-baseline/`](../specs/001-baseline/) ([`spec.md`](../specs/001-baseline/spec.md), [`code-inventory.md`](../specs/001-baseline/code-inventory.md)), initialized with [GitHub Spec Kit](https://github.com/github/spec-kit) (`.specify/`, **Cursor Agent** skills in `.cursor/skills/speckit-*`). The inventory maps **100%** of production code in `src/`. **How to install, initialize, and use Spec Kit:** [`SPEC-KIT.md`](SPEC-KIT.md).
2. **Product behavior** — what **FormKitBundle** guarantees to applications that integrate it (see [`USAGE.md`](USAGE.md), [`CONFIGURATION.md`](CONFIGURATION.md), [`INSTALLATION.md`](INSTALLATION.md)). **PHPUnit** and **PHPStan** enforce contracts in CI where applicable.
3. **Traceability anchors** — stable **`REQ-*`** identifiers in Makefiles and demos (when present) so changes to scripts, ports, and demo workflows stay discoverable from issues and PRs.

There is no separate executable spec language (for example Gherkin); tests and static analysis are the mechanical proof alongside this document.

---

## Table of contents

- [User stories](#user-stories)
- [Bundle functional scope](#bundle-functional-scope)
- [Validating the functional spec](#validating-the-functional-spec)
- [Requirement identifiers (`REQ-*`)](#requirement-identifiers-req-)
- [Suggested workflow for contributors](#suggested-workflow-for-contributors)
- [GitHub Spec Kit (summary)](#github-spec-kit-summary)
- [Relationship to Engram / external checklists](#relationship-to-engram--external-checklists)
- [See also](#see-also)

## User stories

The sections below state **behavior**; this subsection states **intent** in backlog-friendly form.

| ID | Story |
| --- | --- |
| US-01 | **As a** form author, **I want** convention-based labels/placeholders (`form_snake.field_snake.*`) **so that** I stop duplicating translation keys. |
| US-02 | **As an** integrator, **I want** cascading option merge (global → type → form → field) **so that** defaults apply predictably. |
| US-03 | **As an** integrator, **I want** configurable `attr`, `row_attr`, and `translation_domain` **so that** Bootstrap/Tailwind layouts stay consistent. |
| US-04 | **As a** maintainer, **I want** PHPUnit and PHPStan in CI **so that** form extension regressions are caught early. |
| US-05 | **As a** contributor, **I want** `REQ-*` anchors on scripted flows **so that** PRs cite the same identifiers as this document. |
| US-06 | **As an** integrator, **I want** CSRF-only action forms (`CsrfOnlyType`, `HiddenFieldsCsrfType`, `csrf_action_form()`) that fail closed without form CSRF **so that** single POST actions never render raw `<form>` markup nor accept unprotected submissions ([`CSRF.md`](CSRF.md)). |
| US-07 | **As an** integrator, **I want** rootless GET filter forms (`AbstractGetFilterType`, `GetFilterFormFactory`, `SearchQueryType`) **so that** list filters keep query-string friendly field names ([`CSRF.md`](CSRF.md)). |
| US-08 | **As an** integrator, **I want** per-locale tabs (`locale_tabs` option, `TranslatableMapType`, locale tabs theme) **so that** translatable fields render as accessible tabs with translated, overridable UI strings ([`USAGE.md`](USAGE.md#locale-tabs-one-tab-per-language)). |
| US-09 | **As an** integrator, **I want** optional help modals, input groups, required-label suffixes, checkbox rows and `aria-label` fallbacks for label-less selects **so that** form chrome stays consistent across CSS frameworks ([`USAGE.md`](USAGE.md)). |
| US-10 | **As an** integrator, **I want** an opt-in stateless CSRF token id for anonymous forms **so that** rendering public forms does not start a session ([`CSRF.md`](CSRF.md#stateless-csrf-for-public-forms)). |

**Out of scope for these stories:** guarantees outside the stated public API and outside dependency limits (PHP, Symfony, third-party libraries).

---

## Bundle functional scope

**Goal:** Symfony bundle to reduce repetitive form field options: convention-based labels/placeholder/help (form_snake.field_snake.*), configurable attr/row_attr/translation_domain, and cascading option merge (global → field type → form → field), plus reusable form building blocks (fail-closed CSRF action forms, rootless GET filters, accessible locale tabs, help modal, wizard helpers). Symfony 7.4|8.

**In scope** (mirrors [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md))

- Convention keys and cascading option merge: `FormOptionsMerger`, `FormKitOptionMerger`, named `profiles`, `#[FormKitConfig]`, the four usage strategies (Options / Kit / Controller / Wrapped) and their bound-builder helpers.
- Form type extensions: input group, required label suffix, help modal, checkbox row chrome, locale tabs, stateless CSRF token id, `aria-label` for label-less selects.
- Form types: static blocks, A2lix translations wrapper, `TranslatableMapType`, CSRF-only / hidden-fields CSRF action forms (fail closed through `CsrfProtectionGuard`), rootless GET filter forms (`SearchQueryType`).
- Twig: `csrf_action_form()`, locale tabs functions and theme, form renderer component, framework-specific help-modal shells, bundle translations (`NowoFormKitBundle` domain, 7 locales).
- Frontend assets: the help-modal script (built to `src/Resources/public/help-modal.js`) and the opt-in combobox Stimulus peer (not registered by the bundle).
- Multi-step wizard helpers (`MultiStepFormBuilder`, `MultiStepWizardSession`, factory).
- Documented integration (see root `README.md` and `docs/`).
- Configuration and runtime behavior described in [`CONFIGURATION.md`](CONFIGURATION.md) and [`USAGE.md`](USAGE.md).
- Consumer-facing change notes in [`CHANGELOG.md`](CHANGELOG.md) and [`UPGRADING.md`](UPGRADING.md) when applicable.

**Explicit non-goals**

- Behavior not documented here or in linked integrator docs.
- **`demo/`** trees: illustrative unless a path is explicitly published as stable API in this document.

**Demos** (if present): examples only; not part of the Packagist contract unless services or contracts are explicitly documented as stable.

---

## Validating the functional spec

- Run **`composer qa`** and/or **`make qa`** / **`make release-check`** as documented in [`CONTRIBUTING.md`](CONTRIBUTING.md) (Docker-based flows may apply).
- Run **PHPUnit** and **PHPStan** in CI and locally for code changes.
- New or changed behavior should add or adjust **tests** under `tests/` (or the repo’s documented test layout) rather than relying on prose alone.

---

## Requirement identifiers (`REQ-*`)

| ID | Where | What it marks |
| --- | --- | --- |
| `REQ-MAKE-001` | Root `Makefile` (header) | Docker-driven development workflow (`make up`, `install`, `test`, `qa`, …) inside the bundle container. |
| `REQ-MAKE-002` | Root `Makefile` (`release-check`) | Full pre-release chain: git hygiene, open PRs, Twig checks, composer sync, CS, Rector, PHPStan, Igor, translations, coverage gate, demos, Vitest. |
| `REQ-MAKE-004` | Root `Makefile` (`validate-translations`), `.scripts/validate-translations.php`, `ci.yml` | YAML lint + `NowoFormKitBundle.<locale>.yaml` only + key parity with `en`; fails with exit code ≠ 0. |
| `REQ-MAKE-008` / `REQ-MAKE-009` | Root, `demo/` and `demo/symfony8/` Makefiles | Optional soft `-include` of the monorepo `update-deps` helpers (absent on a standalone checkout). |
| `REQ-MAKE-010` | Root and `demo/symfony8/` Makefiles | Compose V2 probe with V1 fallback. |
| `REQ-TEST-003` | Root `Makefile` (`coverage-check`) | PHPUnit line coverage gate (≥ 99%). |
| `REQ-TEST-008` / `REQ-TEST-009` | `.scripts/php-coverage-percent.sh`, `.scripts/ts-coverage-percent.sh` | Print the global PHP / TypeScript coverage percentage after a coverage run. |
| `REQ-TEST-011` | Root `Makefile` (`demo-smoke`), `demo/symfony8/Makefile` (`test-coverage`) | Boot the demo and assert an HTTP 2xx/3xx response. |
| `REQ-CS-008` | Root `Makefile` (`igor`) | Igor FrankenPHP worker-state audit of `src/`. |
| `REQ-GIT-001` | Root `Makefile`, `.githooks/commit-msg`, `.scripts/check-no-*-coauthor.sh`, `ci.yml` (`git-hygiene`) | No Cursor / Claude co-author trailers in commits (hook strips them, audit + CI fail on them). |
| `REQ-REL-003` | `.scripts/check-open-prs.sh` | `release-check` fails while unresolved pull requests are open. |
| `REQ-TWIG-004` | `.scripts/check-twig-extra.sh` | `twig/extra-bundle` + `twig/string-extra` required by the package and enabled in the demo. |
| `REQ-TWIG-005` | Root `Makefile` (`check-no-raw-html-form`), `.scripts/check-no-raw-html-form.sh` | No raw `<form>` / `<input>` / `<textarea>` / `<select>` in bundle or demo Twig (form themes allow-listed). |
| `REQ-SF-002` | `.github/workflows/ci.yml` | CI matrix: PHP 8.2–8.5 × Symfony 7.4 / 8.1 (EOL 8.0 cell dropped, still allowed by `composer.json`). |
| `REQ-SF-003` | `src/DependencyInjection/FormKitExtension.php`, `src/Resources/config/services.yaml` | Form types, type extensions and Twig extensions are tagged through Symfony autoconfiguration (only the per-type stateless CSRF extensions keep an explicit tag). |
| `REQ-DEMO-005` | `demo/symfony8/Makefile` (`up`) | Canonical `make up`: `.env` from `.env.example`, start, `composer install`, print `Demo started at: …`. |
| `REQ-DEMO-007` | `demo/symfony8/Makefile` (`update-bundle`, `release-check`), `demo/Makefile` (`update-bundle-all`, `release-check`) | Sync the mounted bundle (composer update + autoload + cache clear + assets) before demo tests and verification. |
| `REQ-DEMO-013` | `demo/symfony8/Makefile` (`test-e2e`, `demo-screenshots`), `demo/symfony8/e2e/` | Playwright happy path and README WebP screenshots. |

When you change scripted behavior, **update the existing `REQ-*` comment** if the ID still matches the rule, or **add a new `REQ-*`** and document it here and in the PR description.

---

## Suggested workflow for contributors

1. **Clarify behavior** in an issue or draft PR: acceptance criteria for the **product** and, if relevant, **Makefiles/demos** (`REQ-*`).
2. **Implement** with tests and static analysis.
3. **Anchor scripts and demos** when dev UX changes: add or adjust `REQ-*` comments and this table.
4. **Ship integrator docs** when behavior or configuration changes: [`USAGE.md`](USAGE.md), [`CONFIGURATION.md`](CONFIGURATION.md), [`CHANGELOG.md`](CHANGELOG.md), and [`UPGRADING.md`](UPGRADING.md) when consumers must change code or config.
5. **Keep Spec Kit artifacts in sync** when production code under `src/` changes:
   - Update [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) and [`code-inventory.md`](../specs/001-baseline/code-inventory.md).
   - Follow the maintainer checklist in [`SPEC-KIT.md`](SPEC-KIT.md).
   - For **new features**, use Cursor Agent skills (`/speckit-specify`, `/speckit-plan`, `/speckit-tasks`) as documented in SPEC-KIT.

---


## GitHub Spec Kit (summary)

This repository uses [GitHub Spec Kit](https://github.com/github/spec-kit) with **Cursor Agent** (`cursor-agent` integration).

| Artifact | Path |
| --- | --- |
| **Operator manual** (install, init, usage) | [`SPEC-KIT.md`](SPEC-KIT.md) |
| Baseline spec | [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) |
| Code inventory (100%) | [`specs/001-baseline/code-inventory.md`](../specs/001-baseline/code-inventory.md) |
| Constitution | [`.specify/memory/constitution.md`](../.specify/memory/constitution.md) |
| Cursor Agent skills | [`.cursor/skills/`](../.cursor/skills/) (`speckit-*`) |

**Quick start (maintainers):**

```bash
# Install Specify CLI (once per machine) — see SPEC-KIT.md
specify init --here --force --integration cursor-agent --script sh
specify integration list   # Cursor → installed (default)
```

In Cursor Agent, start a new feature with `/speckit-specify <description>`. For day-to-day tooling details, skills reference, folder layout, and troubleshooting, read **[`SPEC-KIT.md`](SPEC-KIT.md)**.

---

## Relationship to Engram / external checklists

[`ENGRAM.md`](ENGRAM.md) covers Nowo-wide documentation checklist items. This document ties together **what the package does**, **how we verify it**, and **local `REQ-*` habits**. Both coexist: Engram for org-level compliance, this file for product + traceability expectations.

---

## See also

- [`SPEC-KIT.md`](SPEC-KIT.md) — GitHub Spec Kit manual (install, structure, usage)
- [`USAGE.md`](USAGE.md)
- [`CONFIGURATION.md`](CONFIGURATION.md)
- [`CONTRIBUTING.md`](CONTRIBUTING.md)
- [`RELEASE.md`](RELEASE.md)
