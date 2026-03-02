# Usage

## FormOptionsMerger service

The **FormOptionsMerger** resolves final options for each field with cascading merge. It uses the configured `configs` and `default_config`: the selected config (or the one passed to `resolve()`) provides `translation_domain`, `defaults`, and `field_types`.

1. **Config defaults:** Convention keys `form_snake.field_snake.label`, `.placeholder`, `.help`, plus `translation_domain`, `attr` and `row_attr` from the active config.
2. **Field type defaults:** From the config’s `field_types` (key = short name like `text` or FQCN).
3. **Field options:** What you pass to `addWithDefaults()` or `buildFormFromArray()`; last wins. Use `label: false`, `placeholder: false` or `help: false` to disable the convention for that key.

You can inject **FormOptionsMerger** and call `resolve($formName, $fieldName, $type, $options, $configName)` directly (e.g. when building a form in the controller without a FormType class).

## Using FormOptionsTrait

1. **Register your form type as a service** and inject the merger:

```yaml
# config/services.yaml
App\Form\UserProfileType:
    tags: ['form.type']
    calls:
        - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
```

2. **In your form type**, use the trait and either the **Phase 2 helpers** (`addText()`, `addEmail()`, …), `addWithDefaults()`, or **buildFormFromArray()**:

**Phase 2 (field name + options only, no type class):**

```php
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserProfileType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addText($builder, 'full_name', []);
        $this->addEmail($builder, 'email_address', []);
        $this->addTextarea($builder, 'message', []);
        $this->addText($builder, 'internal_note', ['label' => false]); // no label
    }
}
```

**Building the form from an array:** Define all fields in one array and call `buildFormFromArray($builder, $fields)`. Each key is the field name; the value is either the type FQCN (e.g. `TextType::class`) or an array with a required `type` key and any other options. Options are still merged by convention and config.

```php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

$this->buildFormFromArray($builder, [
    'full_name' => TextType::class,
    'email_address' => EmailType::class,
    'topic' => ['type' => ChoiceType::class, 'choices' => ['Support' => 'support', 'Other' => 'other']],
]);
```

**Or** use `addWithDefaults($builder, $name, TextType::class, $options)` when you need a type not covered by the helpers or a custom type.

Available Phase 2 helpers: `addText`, `addEmail`, `addTextarea`, `addPassword`, `addUrl`, `addInteger`, `addNumber`, `addCheckbox`, `addChoice`.

The form block prefix (e.g. `user_profile` for `UserProfileType`) is used automatically. Field names are used as-is for the translation key segment (use snake_case for consistency: `full_name`, `email_address`).

## FormKitTrait and FormKitAbstractType (snake_case types)

If you prefer **snake_case type names** instead of FQCNs, use **FormKitTrait** with **FormTypeMap**. The bundle registers **FormTypeMap** with built-in types (`text`, `email`, `choice`, etc.) and optional types when the package is present (e.g. `dropzone`, `cropper`, `translations`). You can extend the map via `nowo_form_kit.type_map` in config (see [Configuration](CONFIGURATION.md)).

- **FormKitTrait** provides `addField($builder, $name, $typeSnakeCase, $options)` and `buildFormFromArray($builder, $fields)` where each field’s type is a string (e.g. `'text'`, `'choice'`) instead of a class. It uses **FormOptionsMerger** for the option cascade and **FormTypeMap** for snake_case type resolution.
- **FormKitAbstractType** is a base class that uses FormKitTrait and injects **FormOptionsMerger** and **FormTypeMap** via the constructor, so it works with the same `configs` / `default_config` model as FormOptionsTrait.

Example with FormKitTrait (when both services are available):

```php
$this->buildFormFromArray($builder, [
    'full_name' => 'text',
    'topic' => ['type' => 'choice', 'choices' => ['Support' => 'support', 'Other' => 'other']],
]);
```

## Custom type layer (wrapping third-party types)

To use **your own form types** that wrap third-party types (e.g. UX Dropzone, Cropper) and still get the bundle’s convention (label, placeholder, help, `field_types.*`), extend **AbstractFormKitWrappedType**.

1. Implement **getInnerType()** and return the FQCN of the type you wrap.
2. Use your type with **FormOptionsTrait::addWithDefaults()** or **buildFormFromArray()** (pass your class as the type). The merger will apply convention using your type’s block prefix (derived from the class name, e.g. `DropzoneFieldType` → `dropzone_field`).
3. Optionally register your type in **nowo_form_kit.type_map** (snake_case name) and use it via **FormKitTrait::addField()** with that name.

Example:

```php
// App\Form\Type\DropzoneFieldType
use Nowo\FormKitBundle\Form\AbstractFormKitWrappedType;
use Symfony\UX\Dropzone\Form\DropzoneType;

final class DropzoneFieldType extends AbstractFormKitWrappedType
{
    protected function getInnerType(): string
    {
        return DropzoneType::class;
    }
}
```

```php
// In your form type (with FormOptionsTrait)
$this->addWithDefaults($builder, 'document', DropzoneFieldType::class, []);
```

Translations then use your form prefix + field name (e.g. `dropzone_demo.document.label`, `dropzone_demo.document.help`). The demo’s Dropzone page uses this pattern.

## Translations

Add entries in your translation domain (e.g. `messages` or a custom one set in config):

```yaml
# translations/messages.en.yaml
user_profile:
  full_name:
    label: Full name
    placeholder: Enter your full name
    help: As shown on your ID.
  email_address:
    label: Email address
    placeholder: you@example.com
    help: We will not share it.
```

## Disabling convention for a key

Pass `false` in the options to omit the convention-based key:

```php
$this->addText($builder, 'internal_note', [
    'label' => false,       // no label
    'placeholder' => false, // no placeholder
    'help' => false,        // no help
]);
```

## Custom static blocks in the form (HR, alert)

When rendering forms with the form_renderer loop (or any `form_row` loop), you can insert **non-input** blocks such as a horizontal rule or a translatable alert. The bundle provides two form types for this:

- **StaticSeparatorType** – Renders an `<hr>` in the form flow. Add it like any other field; it is not mapped and has no label.
- **StaticAlertType** – Renders a Bootstrap-style alert with a translatable message. Options: `message` (required, translation key), `alert_type` (e.g. `info`, `warning`, `success`), `translation_domain`.

**1. Register the form theme** so Twig knows how to render these types:

```yaml
# config/packages/twig.yaml
twig:
  form_themes:
    - '@NowoFormKit/form/static_blocks.html.twig'
```

**2. Add the types to your form** (e.g. in `buildFormFromArray` or with `addWithDefaults`):

```php
use Nowo\FormKitBundle\Form\Type\StaticAlertType;
use Nowo\FormKitBundle\Form\Type\StaticSeparatorType;

$this->buildFormFromArray($builder, [
    'full_name' => TextType::class,
    'message' => TextareaType::class,
    '_notice' => [
        'type' => StaticAlertType::class,
        'message' => 'my_form.notice_message',
        'label' => false,
    ],
    '_sep' => ['type' => StaticSeparatorType::class, 'label' => false],
    'accept_terms' => CheckboxType::class,
]);
```

They will appear in order when you use the form_renderer or iterate with `{% for child in form %}{{ form_row(child) }}{% endfor %}`. Add the translation for `my_form.notice_message` in your domain.

## Input group (icon at start or end)

You can add a prefix or suffix to any field so it renders inside Bootstrap’s **input-group** (e.g. `@` for email, 🔒 for password). The bundle adds two options to all form types via **InputGroupExtension**:

- **input_group_prefix** – Rendered in a `<span class="input-group-text">` before the widget.
- **input_group_suffix** – Rendered in a `<span class="input-group-text">` after the widget.

Use the bundle’s form theme (`@NowoFormKit/form/static_blocks.html.twig`); when either option is set, the row wraps the widget in an `input-group` div. You can pass plain text (e.g. `'@'`) or HTML (e.g. an icon `<i class="bi bi-envelope">`); the theme outputs it with `|raw`.

Example:

```php
$this->buildFormFromArray($builder, [
    'email_address' => [
        'type' => EmailType::class,
        'input_group_prefix' => '@',
    ],
    'password' => [
        'type' => PasswordType::class,
        'input_group_prefix' => '🔒',
    ],
    'website' => [
        'type' => UrlType::class,
        'input_group_suffix' => '🔗',
    ],
]);
```

## Multi-step forms (array-based wizard)

You can define a multi-step wizard as an array and use **MultiStepFormBuilder** plus **MultiStepWizardSessionFactory** to build the form for the current step with the same convention-based options (label, placeholder, help). Each step’s form name for conventions is `{wizardName}_{stepKey}` (e.g. `demo_wizard_contact`, `demo_wizard_address`).

### Steps definition

Define steps as an associative array: step key → `label` and `fields`. The `fields` array uses the same format as **buildFormFromArray** (field name => type FQCN or `['type' => ..., ...options]`). Step order is the order of array keys.

```php
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

$steps = [
    'contact' => [
        'label' => 'Contact',
        'fields' => [
            'fullName' => TextType::class,
            'email' => EmailType::class,
        ],
    ],
    'address' => [
        'label' => 'Address',
        'fields' => [
            'street' => TextType::class,
            'number' => TextType::class,
            'postalCode' => TextType::class,
            'city' => TextType::class,
            'province' => TextType::class,
        ],
    ],
    'confirm' => [
        'label' => 'Confirm',
        'fields' => [], // optional summary step
    ],
];
```

### Services

- **MultiStepFormBuilder::createStepForm(** `string $wizardName`, `string $stepKey`, `array $fieldsDefinition`, `array $data = []`, `?string $configName = null` **): FormInterface**  
  Builds a form containing only the fields for that step, with options merged via FormOptionsMerger (convention keys `{wizardName}_{stepKey}.{field_snake}.label`, etc.).

- **MultiStepWizardSessionFactory::create(** `array $steps`, `string $wizardName` **): MultiStepWizardSession**  
  Returns a session-backed wizard that stores current step index and collected data per step. Use it to get the current step key, set step data after a valid submit, advance, and check completion.

### Controller example

```php
$wizard = $this->wizardFactory->create($steps, 'my_wizard');

if ($wizard->isComplete()) {
    return $this->render('wizard/summary.html.twig', ['wizard' => $wizard]);
}

$stepKey = $wizard->getCurrentStepKey();
$form = $this->multiStepFormBuilder->createStepForm(
    'my_wizard',
    $stepKey,
    $wizard->getStepFields($stepKey),
    $wizard->getCollectedData()[$stepKey] ?? []
);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $wizard->setStepData($stepKey, $form->getData());
    $wizard->advance();
    return $this->redirectToRoute('app_wizard');
}

return $this->render('wizard/step.html.twig', ['form' => $form, 'wizard' => $wizard]);
```

Translation keys for each step follow the same pattern: e.g. `demo_wizard_contact.full_name.label`, `demo_wizard_contact.email.placeholder`, `demo_wizard_address.street.help`.

## Form renderer component (Twig)

The bundle provides a reusable Twig component that outputs `form_start`, all unrendered fields (via `form_rest`), an optional **buttons block**, and `form_end`. You control how many and which submit buttons (or links) appear by passing HTML into `form_buttons`. No single-submit limitation.

**Template:** `@NowoFormKit/components/form_renderer.html.twig`

**Variables:**

| Variable             | Required | Description |
|----------------------|----------|-------------|
| `form`               | yes      | The form view. |
| `form_start_options` | no       | Options for `form_start()` (default: `{}`). |
| `form_button_names`  | no       | Array of form child names (e.g. `['save', 'cancel']`) to render in the buttons area. Use when buttons are form types (SubmitType/ButtonType). |
| `form_buttons`       | no       | HTML for one or more submit/buttons or links. Can be combined with `form_button_names`. |

**Submit as form type (recommended when you need to detect which button was clicked):**

```twig
{# In PHP: $builder->add('save', SubmitType::class); $builder->add('cancel', SubmitType::class); #}
{{ include('@NowoFormKit/components/form_renderer.html.twig', { form: form, form_button_names: ['save', 'cancel'] }) }}
```

**Single submit (HTML):**

```twig
{% set form_buttons %}
  <button type="submit" class="btn btn-primary">{{ 'Submit'|trans }}</button>
{% endset %}
{{ include('@NowoFormKit/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

**Multiple submits (HTML):**

```twig
{% set form_buttons %}
  <button type="submit" name="action" value="save" class="btn btn-primary">Guardar</button>
  <button type="submit" name="action" value="save_and_new" class="btn btn-outline-secondary">Guardar y nuevo</button>
  <a href="{{ path('app_list') }}" class="btn btn-link">Cancelar</a>
{% endset %}
{{ include('@NowoFormKit/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

**Form-type buttons plus extra HTML (e.g. Cancel link):**

```twig
{% set form_buttons %}<a href="{{ path('app_list') }}" class="btn btn-link">Cancelar</a>{% endset %}
{{ include('@NowoFormKit/components/form_renderer.html.twig', { form: form, form_button_names: ['save', 'save_and_new'], form_buttons: form_buttons }) }}
```

The buttons are wrapped in a `<div class="form-kit-buttons">` for styling. When you use `form_button_names`, the component renders the rest of the form first, then those children in the buttons div; otherwise it uses `form_rest()` for good performance and correct CSRF handling.

## Layout examples (Bootstrap and Tailwind)

The bundle does not enforce a CSS framework, but `defaults.attr` and `defaults.row_attr` make it easy to standardize markup per project/theme.

### Bootstrap 5 example

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    default_config: bootstrap
    configs:
        bootstrap:
            alias: bootstrap
            translation_domain: messages
            defaults:
                attr:
                    class: 'form-control'
                row_attr:
                    class: 'mb-3'
            field_types:
                checkbox:
                    attr:
                        class: 'form-check-input'
                    row_attr:
                        class: 'form-check mb-3'
                choice:
                    attr:
                        class: 'form-select'
```

Typical Twig rendering (using the bundle form renderer component):

```twig
{% set form_buttons %}
  <button type="submit" class="btn btn-primary">Save</button>
{% endset %}
{{ include('@NowoFormKit/components/form_renderer.html.twig', { form: form, form_buttons: form_buttons }) }}
```

### Tailwind CSS example

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    default_config: tailwind
    configs:
        tailwind:
            alias: tailwind
            translation_domain: messages
            defaults:
                attr:
                    class: 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
                row_attr:
                    class: 'mb-4'
            field_types:
                textarea:
                    attr:
                        class: 'block w-full min-h-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'
                checkbox:
                    attr:
                        class: 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500'
                    row_attr:
                        class: 'flex items-center gap-2 mb-4'
```

Recommended Tailwind submit button:

```twig
<button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
  Save
</button>
```
