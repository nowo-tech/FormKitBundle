# Installation

This guide covers installing Form Kit Bundle in a Symfony application.

## Requirements

- **PHP** >= 8.2 (minimum)
- **Symfony** ^7.4 || ^8.0 — minimum supported line is **Symfony 7.4**; Symfony 8.0 and 8.1 are also supported
- **symfony/form** (included in framework-bundle)
- **symfony/translation** (included in framework-bundle)

### PHP and Symfony matrix

| Symfony | Minimum PHP (bundle) | Minimum PHP (Symfony) |
|---------|----------------------|------------------------|
| 7.4     | 8.2                  | 8.2                    |
| 8.0     | 8.4                  | 8.4                    |
| 8.1     | 8.4                  | 8.4                    |

Symfony 8.x requires PHP 8.4 or higher. On PHP 8.2–8.3, use Symfony 7.4.

## Install with Composer

```bash
composer require nowo-tech/form-kit-bundle
```

Use a constraint such as `^2.0` to stay on the current major version.

## Register the bundle

### With Symfony Flex

If you use Symfony Flex and the bundle is installed from Packagist, the recipe (when available in [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib)) will register the bundle, create `config/packages/nowo_form_kit.yaml`, and add the Form Kit Twig form theme (`nowo_form_kit_twig.yaml`). The maintained stub lives in this repo — see [RECIPE.md](RECIPE.md). Until the recipe is published upstream, register the bundle and config manually as below.

### Manual registration

1. **Register the bundle** in `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\FormKitBundle\NowoFormKitBundle::class => ['all' => true],
];
```

2. **Create configuration** (optional). Create `config/packages/nowo_form_kit.yaml`:

```yaml
nowo_form_kit:
    default_profile: default
    profiles:
        default:
            alias: default
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control'
                row_attr:
                    class: 'mb-3'
            help_modal:
                framework: bootstrap5
                icon_html: '<span class="nowo-help-modal-icon" aria-hidden="true">?</span>'
            field_types: {}
```

If omitted, the bundle uses a single default profile with `translation_domain: messages` and empty attr/row_attr. See [Configuration](CONFIGURATION.md).

### Help modal assets (REQ-ASSETS-004)

1. After install/upgrade, publish bundle public files once:

```bash
php bin/console assets:install public
```

2. Load the script (and optional CSS) with the **`nowo_form_kit`** asset package registered by the bundle (`prepend()` → `framework.assets.packages.nowo_form_kit`, `base_path: /bundles/nowoformkit`):

```twig
<script defer src="{{ asset('help-modal.js', 'nowo_form_kit') }}"></script>
<link rel="stylesheet" href="{{ asset('help-modal.css', 'nowo_form_kit') }}">
```

Do **not** hard-code `/bundles/nowoformkit/...` or duplicate the package in the app’s `framework.yaml`. See [Usage — Help modal](USAGE.md#help-modal-optional).

## Using in form types

1. Register your form type as a service and inject **FormOptionsMerger** (see [Usage](USAGE.md)).
2. Use the **Options** strategy: **FormOptionsTrait** with `withBuilder()` + `addTextField()`, or `addText()`, `addEmail()`, … or `buildFormFromArray()` in `buildForm()`. See [Usage — strategies](USAGE.md#usage-strategies).
3. Optionally call `setFormKitConfigName('profile_name')` to use a different profile than `default_profile`.
4. Add translation keys for `{form_snake}.{field_snake}.label`, `.placeholder`, `.help` in your translation domain.

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
