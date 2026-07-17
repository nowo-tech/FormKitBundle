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

If you use Symfony Flex and the bundle is installed from Packagist, the recipe (when available in [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib)) will register the bundle and create `config/packages/nowo_form_kit.yaml`. Until then, register the bundle and config manually as below.

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
    default_config: default
    configs:
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

If omitted, the bundle uses a single default config with `translation_domain: messages` and empty attr/row_attr. See [Configuration](CONFIGURATION.md). To use **help modal** in the browser, install bundle assets and load `bundles/nowoformkit/help-modal.js` after Bootstrap; see [Usage — Help modal](USAGE.md#help-modal-optional).

## Using in form types

1. Register your form type as a service and inject **FormOptionsMerger** (see [Usage](USAGE.md)).
2. Use **FormOptionsTrait** and call `withBuilder()` + `addTextField()`, or `addText()`, `addEmail()`, … or `buildFormFromArray()` in `buildForm()`.
3. Optionally call `setFormKitConfigName('config_name')` to use a different config than `default_config`.
4. Add translation keys for `{form_snake}.{field_snake}.label`, `.placeholder`, `.help` in your translation domain.

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
