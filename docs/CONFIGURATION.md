# Configuration

The bundle is configured under the root key `nowo_form_kit`. Multiple configs can coexist; each is identified by a name and has an `alias` and the usual options.

## Structure

| Option | Type | Description |
|--------|------|-------------|
| `default_config` | `string` | Name (key) of the config to use when a form does not specify one. Must be a key in `configs`. Default: `default`. |
| `configs` | `array` | Named configs. Key = config name (e.g. `default`, `bootstrap`). Each value has: |
| `configs.<name>.alias` | `string` | **Required.** Alias for this config (e.g. for reference in form types or UI). |
| `configs.<name>.translation_domain` | `string` | Translation domain for labels, placeholders, help. Default: `messages`. |
| `configs.<name>.defaults.attr` | `array` | Default HTML attributes for every field (e.g. `class: form-control`). |
| `configs.<name>.defaults.row_attr` | `array` | Default HTML attributes for the form row wrapper (e.g. `class: mb-3`). |
| `configs.<name>.field_types` | `array` | Per-field-type default options. Key = short type name (e.g. `text`, `email`) or FQCN. Value = options array. |
| `type_map` | `array` | Additional form type names (snake_case) => FQCN. Merged with built-in and optional types (e.g. Dropzone, Cropper, A2lix Translations when the package is installed). Use for custom types or to override. |

**Legacy:** If `configs` is not set (or empty), the root-level `translation_domain`, `defaults` and `field_types` are used to build a single config named `default`, so existing YAML keeps working.

## Example with multiple configs

```yaml
# config/packages/nowo_form_kit.yaml
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
            field_types:
                text:
                    attr: { class: 'form-control' }
        bootstrap:
            alias: bootstrap
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control form-control-lg'
                row_attr:
                    class: 'mb-3'
            field_types: {}
```

## Optional and custom types (type_map)

The bundle registers a **FormTypeMap** that resolves snake_case type names to form type FQCNs. Built-in types include: `text`, `email`, `textarea`, `password`, `url`, `integer`, `number`, `checkbox`, `choice`. Optional types are added when the class exists: `dropzone` (Symfony UX Dropzone), `cropper` (Symfony UX Cropper.js), `translations` (A2lix TranslationFormBundle). You can add more in config:

```yaml
nowo_form_kit:
    type_map:
        my_upload: 'App\Form\Type\MyUploadType'
```

This is used when building forms with **FormKitTrait** / **FormKitAbstractType** (e.g. `addField($builder, 'file', 'dropzone', [])` or `buildFormFromArray()` with snake_case types).

## Using a specific config in a form type

Inject the merger and call `setFormKitConfigName('bootstrap')` (or the config name you want) so that form uses that config instead of `default_config`. For example in `config/services.yaml`:

```yaml
App\Form\MyBootstrapFormType:
    tags: ['form.type']
    calls:
        - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
        - setFormKitConfigName: ['bootstrap']
```

Convention-based keys (label, placeholder, help) are derived from `{form_snake}.{field_snake}.label`, etc., unless you pass `label: false`, `placeholder: false` or `help: false` in the field options.
