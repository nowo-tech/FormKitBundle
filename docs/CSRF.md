# CSRF-only and GET filter forms

Form Kit ships helpers for two common host patterns:

1. **CSRF-only POST actions** (toggle, revoke, delete) — token + optional flat hidden fields
2. **Rootless GET filters** — query-string friendly field names without CSRF

## CSRF-only action forms

### Types

| Class | Role |
|-------|------|
| `Nowo\FormKitBundle\Form\Type\CsrfOnlyType` | Empty form body; CSRF protection only; empty block prefix |
| `Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType` | CSRF + typed flat fields (default `hidden`) |

Both extend `FormKitAbstractType` and are registered as `form.type` services.

### Factory

Inject `Nowo\FormKitBundle\Form\CsrfOnlyFormFactory`:

```php
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;

final class ProjectController
{
    public function __construct(
        private CsrfOnlyFormFactory $csrfOnlyFormFactory,
    ) {
    }

    public function revoke(string $id): Response
    {
        // Flat `_token` (empty form name / empty block prefix)
        $form = $this->csrfOnlyFormFactory->create(
            action: $this->generateUrl('project_revoke', ['id' => $id]),
            csrfTokenId: 'project_revoke_'.$id,
        );

        // Nested `csrf_only[_token]`
        $named = $this->csrfOnlyFormFactory->createNamed(
            action: $this->generateUrl('project_delete', ['id' => $id]),
            csrfTokenId: 'project_delete_'.$id,
        );

        // CSRF + flat fields (e.g. flags / redirects)
        $withFields = $this->csrfOnlyFormFactory->createWithFields(
            action: $this->generateUrl('project_toggle', ['id' => $id]),
            csrfTokenId: 'project_toggle_'.$id,
            fields: [
                'enabled'  => '1',
                'redirect' => $this->generateUrl('project_show', ['id' => $id]),
            ],
            fieldTypes: [
                'enabled' => 'checkbox',
            ],
        );

        // …
    }
}
```

### Twig

Render with the usual Symfony form helpers (empty prefix → flat field names):

```twig
{{ form_start(form) }}
  {{ form_widget(form) }}
  <button type="submit">Revoke</button>
{{ form_end(form) }}
```

Pass a unique `csrf_token_id` per action. Optionally override `csrf_field_name` (default `_token`) for kit UIs that expect `_csrf_token`.

## GET filter forms

### Abstract base

Extend `Nowo\FormKitBundle\Form\AbstractGetFilterType` for list / dashboard filters:

- `#[FormKitConfig('filter')]` — host must define a `filter` profile (typically `defaults.label: false`, `defaults.required: false`, `auto_placeholder` / `auto_help`)
- Defaults: `csrf_protection: false`, `method: GET`, `data_class: null`
- Helpers: `addHiddenFilterField()`, `addFilterSelect()`

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    profiles:
        filter:
            translation_domain: form
            auto_placeholder: true
            auto_help: true
            defaults:
                label: false
                required: false
```

### Rootless factory

Use `Nowo\FormKitBundle\Form\GetFilterFormFactory` so field names stay top-level query params:

```php
use Nowo\FormKitBundle\Form\GetFilterFormFactory;

$form = $this->getFilterFormFactory->create(ProjectListFilterType::class, [
    'q' => $request->query->getString('q'),
]);
```

### Built-in search type

`Nowo\FormKitBundle\Form\Type\SearchQueryType` is a rootless GET form with a single `q` field (`search` snake type → Symfony `SearchType`).

```php
$form = $formFactory->create(SearchQueryType::class, null, [
    'action' => $this->generateUrl('admin_search'),
    'q' => $request->query->getString('q'),
    'input_attr' => ['class' => 'input', 'aria-label' => 'Search'],
]);
```
