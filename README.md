# Form Kit Bundle

[![CI](https://github.com/nowo-tech/FormKitBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/FormKitBundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/form-kit-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/form-kit-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/form-kit-bundle.svg)](https://packagist.org/packages/nowo-tech/form-kit-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net) [![Symfony](<https://img.shields.io/badge/Symfony-7.4%20%7C%208.0%20%7C%208.1-000000?logo=symfony>)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/FormKitBundle.svg?style=social&label=Star)](https://github.com/nowo-tech/FormKitBundle) [![Coverage](https://img.shields.io/badge/Coverage-99.6%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** Install from [Packagist](https://packagist.org/packages/nowo-tech/form-kit-bundle) and give the repo a star on GitHub.

Symfony bundle to reduce repetitive form field options: convention-based translation keys (`form_snake.field_snake.label`, `.placeholder`, `.help`), configurable defaults and multiple configs via YAML, and cascading option merge (global → field type → form → field).

**Minimum requirements: PHP 8.2 and Symfony 7.4.** Also compatible with Symfony 8.0 and 8.1 (require PHP 8.4+).

## Features

- **Usage strategies:** **Options** (`FormOptionsTrait` + FQCN), **Kit** (`FormKitTrait` / `FormKitAbstractType` + snake_case), **Controller** (`FormKitControllerTrait`), **Wrapped** (`AbstractFormKitWrappedType`). See [Usage — strategies](docs/USAGE.md#usage-strategies).
- **Convention-based labels, placeholder and help:** Default translation keys are `{form_snake}.{field_snake}.label`, `.placeholder`, `.help`. Set any to `false` in field options to disable.
- **Multiple configs:** Define named configs (e.g. `default`, `bootstrap`) with `translation_domain`, `defaults.attr`, `defaults.row_attr`, `by_form`, and per-field-type options. Choose the active config per form via `#[FormKitConfig('bootstrap')]` or `setFormKitConfigName()` (**named-config** technique).
- **Cascading merge:** Options are merged in order: config defaults → field type → `by_form` → field options. Explicit field options override.
- **Options strategy helpers:** `addText()`, `addEmail()`, … Prefer **`withBuilder($builder, …)` + `addTextField()`** (**bound-builder**). Or **array-build** with `buildFormFromArray()`.
- **Choice presets:** `addSelect()`, `addMultiSelect()`, `addChoiceRadios()`, `addChoiceCheckboxes()`, plus `addMultiSelectSelectAll()` when **nowo-tech/select-all-choice-bundle** is installed (optional Composer **suggest**).
- **FQCN helpers:** `addAutocompleteField()` for Symfony UX Autocomplete types, `addCKEditorField()` when **friendsofsymfony/ckeditor-bundle** is installed; `addDropzone()` / `addCropper()` when the UX packages are installed.
- **Model transformers:** `addSwitchType()`, `addJsonType()`, `addBoolType()`, `addMoneyType()`, `addCsvType()` (same helpers on **FormKitControllerTrait** with `*Type` suffix).
- **Optional types:** FormTypeMap includes core Symfony types plus optional UX (Dropzone, Cropper) and A2lix when installed. Extend via `type_map`.
- **Form type extensions:** **InputGroupExtension**, **RequiredLabelSuffixExtension**, **HelpModalExtension**. See [Configuration](docs/CONFIGURATION.md) and [Usage](docs/USAGE.md#help-modal-optional).

## Installation

```bash
composer require nowo-tech/form-kit-bundle
```

With Flex, the recipe creates `config/packages/nowo_form_kit.yaml`. Otherwise register the bundle in `config/bundles.php` and add the config file manually. See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Quick usage (**Options** strategy)

1. **Configure** (optional) — edit `config/packages/nowo_form_kit.yaml`: set `default_config`, `configs` (each with `alias`, `translation_domain`, `defaults`, `field_types`), and optionally `type_map` for custom or UX types.
2. **Register your form as a service** and inject **FormOptionsMerger**:

```yaml
# config/services.yaml
App\Form\UserProfileType:
  tags: ['form.type']
  calls:
    - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
```

3. **Use `FormOptionsTrait`** (**bound-builder** or **array-build**):

```php
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserProfileType extends AbstractType
{
  use FormOptionsTrait;

  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $this->withBuilder($builder, function (): void {
      $this->addTextField('full_name');
      $this->addEmailField('email_address');
    });
    // Or: $this->buildFormFromArray($builder, ['full_name' => TextType::class, 'email_address' => EmailType::class]);
  }
}
```

4. **Add translations** for keys like `user_profile.full_name.label`, `user_profile.full_name.placeholder`, `user_profile.full_name.help` in your translation domain.

## Demos

The bundle includes a **Symfony 8** demo that runs with **FrankenPHP** (Caddy + PHP in Docker). **`docker-compose`** defaults to **`APP_ENV=dev`**, so the entrypoint uses **Caddyfile.dev** (no PHP worker), and Twig/PHP changes are visible on refresh. **Worker mode** applies to a production-style setup — see [docs/DEMO-FRANKENPHP.md](docs/DEMO-FRANKENPHP.md). The demo has:

- **Locale in the URL** — routes are under `/{locale}/…` (`en`, `es`, `fr`, `de`); `/` redirects to the default locale.
- **FormType** example (contact, `buildFormFromArray`), **help modal** sample (`help-modal.js` + `assets:install`; include `@NowoFormKit/help_modal/shells.html.twig` for overridable modal shells).
- Form built in the **controller** (`FormKitControllerTrait` / `FormOptionsMerger::resolve()`).
- **Search**, **example**, **Dropzone**, **Cropper**, **translations** (A2lix), **nested**, **data transformers**, **choice fields**, **conditional fields** (incl. Live Component), **Kit API patterns** (snake_case `FormKitAbstractType` + named config), **Nowo special fields** (OTP, phone, password, icon selector, Tiptap, CKEditor 5 — see [demo/README.md](demo/README.md)), **CKEditor** (FOSCKEditorBundle), **UX Autocomplete**, **multi-step** wizard.

Run a demo via Docker/Make from the bundle root; see [demo/README.md](demo/README.md) and [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md).

For CSS frameworks, see [docs/USAGE.md](docs/USAGE.md) for ready-to-use configuration examples for **Bootstrap 5** and **Tailwind CSS**.

## Documentation

Developer-facing docs and comments (Markdown, PHPDoc, JSDoc) are **English only**; see [Contributing — Language policy](docs/CONTRIBUTING.md#language-policy).

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Flex recipe](docs/RECIPE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)
- [Roadmap](docs/ROADMAP.md)

### Additional documentation

- [Demo with FrankenPHP (development and production)](docs/DEMO-FRANKENPHP.md)
- [Help modal (field option + frontend script)](docs/USAGE.md#help-modal-optional)
- [Overriding bundle templates](docs/USAGE.md#overriding-bundle-templates)

## Tests and coverage

- Tests: PHPUnit (PHP), Vitest (TypeScript)
- PHP: 99.59%
- TS/JS: 100%
- Python: N/A

## License

MIT. See [LICENSE](LICENSE).
