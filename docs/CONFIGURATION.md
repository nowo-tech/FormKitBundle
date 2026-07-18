# Configuration

The bundle is configured under the root key `nowo_form_kit`. Multiple configs can coexist; each is identified by a name and has an `alias` and the usual options.

## Structure

| Option | Type | Description |
|--------|------|-------------|
| `default_config` | `string` | Name (key) of the config to use when a form does not specify one. Must be a key in `configs`. Default: `default`. |
| `configs` | `array` | Named configs. Key = config name (e.g. `default`, `bootstrap`). Each value has: |
| `configs.<name>.alias` | `string` | **Required.** Alias for this config (e.g. for reference in form types or UI). |
| `configs.<name>.translation_domain` | `string` | Translation domain for labels, placeholders, help. Default: `messages`. |
| `configs.<name>.required_label_suffix` | `string\|null` | Appended to the label when the field is required (e.g. ` *`). `null` or empty disables. **`RequiredLabelSuffixExtension`** reads this from the config whose key equals **`default_config`** (not from the form’s `setFormKitConfigName()`), and injects `required_label_suffix` into the view for all forms. |
| `configs.<name>.defaults.attr` | `array` | Default HTML attributes for every field (e.g. `class: form-control`). |
| `configs.<name>.defaults.row_attr` | `array` | Default HTML attributes for the form row wrapper (e.g. `class: mb-3`). |
| `configs.<name>.field_types` | `array` | Per-field-type default options. Key = short type name (e.g. `text`, `email`) or FQCN. Value = options array (may include `constraints`). |
| `configs.<name>.by_form` | `array` | Per-form defaults keyed by form name / block prefix (e.g. `user_profile`). Each entry may set `defaults.attr` / `defaults.row_attr` and `fields.<field>` overrides (including `constraints`). Merged **after** `field_types`, **before** per-field options. |
| `configs.<name>.constraint_message_convention` | `bool` | When `true`, constraints without an explicit `message` (or `minMessage`/`maxMessage` when `min`/`max` are set) get keys `{form}.{field}.constraints.{Name}` (and `.min` / `.max` suffixes for Length-style). Put those keys in the **validators** catalog. Default: `false`. |
| `configs.<name>.help_modal` | `array` | Default options when a field sets `help_modal: true` (merged with per-field overrides). Keys: `framework` (`bootstrap5`, `bootstrap4`, `tailwind`, `foundation`), `icon_html`, optional `ux_icon` / `ux_icon_attributes` (with **symfony/ux-icons**), `trigger_class`, `aria_label`, `title` / `title_html`, `content`. See [Usage — Help modal](USAGE.md#help-modal-optional). |
| `type_map` | `array` | Additional form type names (snake_case) => FQCN. Merged with built-in and optional types (e.g. Dropzone, Cropper, A2lix Translations when the package is installed). Use for custom types or to override. |

**Legacy:** If `configs` is not set (or empty), the root-level `translation_domain`, `required_label_suffix`, `defaults`, `help_modal`, `field_types`, `by_form`, and `constraint_message_convention` are used to build a single config named `default`, so existing YAML keeps working.

## Cascade order

For each field, `FormOptionsMerger::resolve()` merges:

1. Convention keys (`label` / `help` / `attr.placeholder`) + config `defaults`
2. `field_types.<type>`
3. `by_form.<formName>.defaults`
4. `by_form.<formName>.fields.<fieldName>`
5. Options passed in PHP / `add*()`

Later layers win (scalars replace; nested arrays merge recursively). Constraints are collected from layers 2–5 and instantiated last.

## Per-form defaults (`by_form`)

```yaml
nowo_form_kit:
    configs:
        default:
            alias: default
            translation_domain: messages
            defaults:
                attr: { class: 'form-control' }
                row_attr: { class: 'mb-3' }
            by_form:
                user_profile:
                    defaults:
                        row_attr: { class: 'col-12 mb-4' }
                    fields:
                        email_address:
                            attr: { autocomplete: email }
                            constraints: [ NotBlank, Email ]
            constraint_message_convention: true
```

With `constraint_message_convention: true`, `NotBlank` without an explicit message uses the key `user_profile.email_address.constraints.NotBlank` (add it under `translations/validators.*.yaml` or your validators domain).

## Form type extensions (global)

The bundle registers these **form type extensions** (they apply to all field types unless the option is unused):

| Extension | Purpose |
|-----------|---------|
| **InputGroupExtension** | Options `input_group_prefix` and `input_group_suffix` for Bootstrap-style input groups (requires the bundle form theme; see [Usage](USAGE.md#input-group-icon-at-start-or-end)). |
| **RequiredLabelSuffixExtension** | Appends `required_label_suffix` from the config named **`default_config`** to required field labels. |
| **HelpModalExtension** | Option `help_modal` (`false`, `true`, or array): injects JSON into `label[data-nowo-help-modal]` for the frontend script; see [Usage](USAGE.md#help-modal-optional). |

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

## Suggested optional Composer packages

The bundle core has no hard dependency on UX or third-party form integrations. Composer **`suggest`** entries document optional packages and the corresponding helpers:

| Suggested package | Purpose |
|-------------------|---------|
| **a2lix/translation-form-bundle** | `translations` type in FormTypeMap; `addTranslations()` on traits |
| **nowo-tech/select-all-choice-bundle** | `addMultiSelectSelectAll()` / `addMultiSelectSelectAllType()` (`select_all` on `ChoiceType` multiple) |
| **friendsofsymfony/ckeditor-bundle** | `addCKEditorField()` / `addCKEditorFieldType()` (rich text) |
| **symfony/ux-autocomplete** | Use with `addAutocompleteField()` / `addAutocompleteFieldType()` and your autocomplete FormType FQCN |
| **symfony/ux-dropzone** | `addDropzone()` / `addDropzoneField()` / `addDropzoneFieldType()` |
| **symfony/ux-cropperjs** | `addCropper()` / `addCropperField()` / `addCropperFieldType()` |
| **symfony/ux-live-component** | Optional Live forms; demo `/conditional-fields-live` |
| **symfony/ux-icons** | `help_modal.ux_icon` in field options (IconRenderer) instead of raw `icon_html` only |

Install only what you need; each helper documents runtime requirements (e.g. `LogicException` if a suggested class is missing).

## Optional and custom types (type_map)

The bundle registers a **FormTypeMap** that resolves snake_case type names to form type FQCNs.

**Built-in (always):** `text`, `email`, `textarea`, `password`, `url`, `integer`, `number`, `checkbox`, `choice`, `date`, `datetime`, `time`, `birthday`, `week`, `money`, `percent`, `currency`, `locale`, `language`, `country`, `timezone`, `file`, `hidden`, `tel`, `color`, `range`, `collection`, `repeated`, `submit`, `button`, `reset`, `date_interval`.

**Optional (when the class exists):** `dropzone` (UX Dropzone), `cropper` (UX Cropper.js), `translations` (A2lix). You can add more in config:

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
