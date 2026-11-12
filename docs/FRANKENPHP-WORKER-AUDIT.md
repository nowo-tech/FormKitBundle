# FrankenPHP worker mode audit (`FRANKENPHP_RESET_KERNEL` unset / false)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/form-kit-bundle` (`symfony-bundle`) |
| Audited revision | `v2.22.0` (kept-alive test added 2026-11-12; earlier review kept below) |
| Audit date | 2026-11-12 (earlier audit dates kept in the history) |
| Method | Manual review of every file under `src/` (services, form types, type extensions, traits, DI extension, compiler passes, `Resources/config/services.yaml`) + PHPStan FrankenPHP classic/worker rulesets |
| **Verdict** | ✅ **Compatible** with FrankenPHP worker when the kernel is **not** reset between requests (`FRANKENPHP_RESET_KERNEL` unset or `0`). Bundle services hold no per-request state. Trait setters are **DI / constructor configuration** only — misuse from an action is an integrator error (documented). |

## Table of contents

- [Execution model assumed](#execution-model-assumed)
- [Summary](#summary)
- [Services reviewed](#services-reviewed)
- [Findings](#findings)
  - [W-01 — Public setters on shared form types persist across requests (Accepted / documented)](#w-01--public-setters-on-shared-form-types-persist-across-requests-accepted--documented)
  - [W-02 — `FormKitControllerTrait` naming/profile state (Accepted / documented)](#w-02--formkitcontrollertrait-namingprofile-state-accepted--documented)
  - [W-03 — `MultiStepWizardSession` session binding — **Fixed in 2.5.3**](#w-03--multistepwizardsession-session-binding--fixed-in-253)
- [Kept-alive test (REQ-RUNTIME-002)](#kept-alive-test-req-runtime-002)
- [Usage recommendations](#usage-recommendations)
- [Re-audit triggers](#re-audit-triggers)

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. Symfony Runtime default is **kernel reused** (`FRANKENPHP_RESET_KERNEL` unset/false). Setting `FRANKENPHP_RESET_KERNEL=1` clones the application after each request (escape hatch; lower throughput). This audit targets the **strict** default:

- **A — kernel not reset, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — kernel not reset, no service reset relied upon:** nothing in this bundle needs `kernel.reset`; shared services only hold compiled config.

A bundle that is safe under **B** is safe under **A**, under `FRANKENPHP_RESET_KERNEL=1`, and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | Bundle services only hold compiled config / injected deps |
| Trait setters (form types / controllers) | ✅ documented | Configuration only; see W-01 / W-02 |
| Static properties / `static` locals | ✅ | None; only pure static helpers |
| `ResetInterface` / `kernel.reset` | ✅ N/A | No bundle service needs a reset |
| Request / user / locale in services | ✅ | `MultiStepWizardSession` resolves session via `RequestStack` on every access (W-03 fixed) |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale` | ✅ | None |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ | None |
| Open resources / growing caches | ✅ | None |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` in `phpstan.neon.dist` (`src` + `tests`; optional A2lix type excluded) |

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `FormOptionsMerger` | yes | `$profiles` (parameter, never written after construction) | ✅ | ✅ |
| `FormTypeMap` | yes | `$map`, built once in the constructor | ✅ | ✅ |
| `ConstraintDefinitionFactory` | yes | none | ✅ | ✅ |
| `CssClassUtilities` | yes | none (`final readonly`) | ✅ | ✅ |
| `MultiStepFormBuilder`, `MultiStepWizardSessionFactory`, `CsrfOnlyFormFactory`, `GetFilterFormFactory` | yes | none (`final readonly`) | ✅ | ✅ |
| Form type extensions (`HelpModal`, `RequiredLabelSuffix`, `InputGroup`) | yes | readonly config only | ✅ | ✅ |
| Field widget extensions (`ChoiceCards`, `Switch`, `CharCounter`, 2.22) | yes | none; `CharCounter` holds the optional validator metadata factory (readonly), whose per-class metadata cache is request-independent; covered by `FieldWidgetsThemeTest::testTheSameExtensionsServeDifferentFormsWithoutLeakingState` | ✅ | ✅ |
| `HelpModalNonce`, `HelpModalNonceResponseListener`, `HelpModalTwigExtension` | yes | none (`final readonly` / readonly ctor); the per-request nonce lives in the main request attributes | ✅ | ✅ |
| Built-in form types (`CsrfOnlyType`, `HiddenFieldsCsrfType`, `SearchQueryType`, …) | yes | trait config properties (see findings) | ✅ | ✅ |
| Application form types / controllers using Form Kit traits | yes | setter-backed config (see findings) | ✅ with documented usage | ✅ with documented usage |

`MultiStepWizardSession`, data transformers, and `FormKitOptionMerger` are plain objects created per call; they are not container services.

## Findings

### W-01 — Public setters on shared form types persist across requests (Accepted / documented)

- **Where:** `FormOptionsTrait` / `FormKitTrait` (`setFormKitConfigName`, translations setters).
- **Impact:** Values survive between requests under A and B. Safe when used as DI config (`#[FormKitConfig]`, constructor, or unconditional `buildForm()`). Unsafe if an action sets request-dependent data and a later request skips the setter.
- **Mitigation:** PHPDoc + [USAGE](USAGE.md#frankenphp-worker-mode) require constructor/DI use; pass per-request data through form options; locale resolvers must read `RequestStack` at call time.

### W-02 — `FormKitControllerTrait` naming/profile state (Accepted / documented)

- **Where:** `setFormKitFormName()` / `setFormKitConfigName()` / translations setters on controllers.
- **Impact:** Controllers are shared services; per-action setters leak to later actions in the same worker.
- **Mitigation:** Setters only in the constructor; or pass `$formName` / `$configName` per `add*Type()` call.

### W-03 — `MultiStepWizardSession` session binding — **Fixed in 2.5.3**

- **Was:** constructor captured `RequestStack::getSession()` into a `readonly` property.
- **Now:** session is resolved via `RequestStack` on every read/write. Still create via `MultiStepWizardSessionFactory::create()` inside the action; do not store the object on a shared service.

## Kept-alive test (REQ-RUNTIME-002)

Test: `Nowo\FormKitBundle\Tests\Integration\Worker\WorkerKeptAliveKernelTest` (`tests/Integration/Worker/WorkerKeptAliveKernelTest.php`). It uses one booted kernel (`WorkerKeptAliveKernel`: FrameworkBundle, TwigBundle and the bundle) and drives `http_kernel` `handle()` + `terminate()`; each request builds a Form Kit search form from its own query string and renders it with Twig. `services_resetter` / `kernel.reset` is **never** called. It runs 1,200 iterations alternating two users, asserts every response contains only its own data (nothing of the other user or of the previous iteration), and that memory growth after a 100-iteration warm-up stays below 4 MiB.

Result: no state is carried between requests, so the bundle is **safe with kernel reset false**.

Rule 7: the bundle never clears or resets the host's default EntityManager (it has no persistence).

## Usage recommendations

- Prefer `#[FormKitConfig('…')]` or constructor setters; request-dependent values go through form options / method arguments.
- Controllers: constructor for form name / profile, or pass names per call.
- Create wizard sessions per request through the factory.
- Custom types/extensions: do not store `FormBuilder`, form data, `Request`, or user on properties (`withBuilder()` restores state in `finally`).

## Re-audit triggers

Re-run when a change adds: new mutable properties or setters on traits; a cache in merger / type map / constraint factory; a service injecting `RequestStack`, `TokenStorage`, or session; an event listener/subscriber; or runtime use of `$_SERVER` / `$_ENV`.
