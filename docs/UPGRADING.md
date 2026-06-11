# Upgrading

This document describes how to upgrade between major versions of Form Kit Bundle.

## 2.0.0 (2026-06-11)

### Breaking: raised platform requirements

- **PHP** minimum is now **8.2** (was 8.1).
- **Symfony** minimum is now **^7.4 || ^8.0** (7.4, 8.0, 8.1). Symfony 6.x and Symfony 7.0–7.3 are no longer supported.
- **Symfony 8** requires **PHP 8.4+** (Symfony platform requirement).

If your project runs on PHP 8.1 or Symfony 6.4 / 7.0–7.3, stay on **form-kit-bundle 1.x** (`^1.0`).

### Upgrade steps (from 1.x)

1. **Upgrade your platform** before bumping the bundle:
   - PHP **8.2+** (Symfony 7.4), or PHP **8.4+** (Symfony 8.0 / 8.1).
   - Symfony **7.4** or **8.x**.
2. Update the package constraint:

   ```bash
   composer require nowo-tech/form-kit-bundle:^2.0
   ```

3. **Optional integrations** — `a2lix/translation-form-bundle` is no longer a hard dependency; install it only if you use the `translations` type (`^3.2` on PHP 8.2 / Symfony 7.4, `^4.0` on PHP 8.4+ / Symfony 7.4|8).
4. **Help modal** — if you adopt `help_modal`, run `php bin/console assets:install public` and load `bundles/nowoformkit/help-modal.js` after your CSS framework; see [Usage — Help modal](USAGE.md#help-modal-optional).
5. Review [CHANGELOG](CHANGELOG.md) for new helpers (choice presets, model transformers, **FormKitControllerTrait**) and demo pages as reference.

No configuration key renames are required for existing YAML; public services and extension points remain compatible where the platform allows installation.

## 2.0.x patch releases

### 2.0.1 (2026-06-11)

Repository-only: **demo/symfony6** removed. `make update-deps` and demo Makefiles target **symfony7** / **symfony8** only. No change to the published Composer package or bundle API.

## 1.x

### 1.0.0 (2025-03-03)

First stable release. No upgrade steps required.

- **Requirements:** PHP >= 8.1, Symfony ^6.4 || ^7.0 || ^8.0.
- **Optional:** If you use the `translations` type (A2lix TranslationFormBundle), constraint is `^3.2 || ^4.0` (use 3.x on PHP 8.2, 4.x on PHP 8.4+).
