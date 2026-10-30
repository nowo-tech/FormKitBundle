# CSRF-only and GET filter forms

Form Kit ships helpers for two common host patterns:

1. **CSRF-only POST actions** (toggle, revoke, delete) — token + optional flat hidden fields; also from Twig via `csrf_action_form()`
2. **Rootless GET filters** — query-string friendly field names without CSRF
3. **Stateless CSRF for public forms** (opt-in) — anonymous forms that must not start a session

## CSRF-only action forms

### Types

| Class | Role |
|-------|------|
| `Nowo\FormKitBundle\Form\Type\CsrfOnlyType` | Empty form body; CSRF protection only; empty block prefix |
| `Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType` | CSRF + typed flat fields (default `hidden`) |

Both extend `FormKitAbstractType` and are registered as `form.type` services.

Both **require Symfony's form CSRF extension** (`symfony/security-csrf` with `framework.csrf_protection` and `framework.form.csrf_protection` enabled). Without it they throw a `LogicException` when built instead of silently accepting tokenless POSTs (fail closed); pass `'csrf_protection' => false` only for actions that do not change state. See [SECURITY.md](SECURITY.md#csrf-action-forms-fail-closed).

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

### Twig function `csrf_action_form()`

Since **2.11.0**. Build the same forms straight from a template (shared includes, kit forks, list row buttons) without passing a form from the controller:

```twig
{# csrf_action_form(action, csrfTokenId, method = 'POST', named = true, csrfFieldName = '_token', fields = {}, fieldTypes = {}, fieldOptions = {}) #}

{# Nested csrf_only[_token] (CsrfOnlyFormFactory::createNamed) #}
{% set form = csrf_action_form(path('item_delete', {id: item.id}), 'item_delete_' ~ item.id) %}
{{ form_start(form) }}<button type="submit">Delete</button>{{ form_end(form) }}

{# Flat token with a kit field name (CsrfOnlyFormFactory::create) #}
{% set form = csrf_action_form(path('kit_action'), 'kit_action', named: false, csrfFieldName: '_csrf_token') %}

{# CSRF + flat typed fields (CsrfOnlyFormFactory::createWithFields; always empty prefix) #}
{% set form = csrf_action_form(path('locale_switch', {locale: 'en'}), 'locale', fields: {redirect: app.request.requestUri}) %}
{% set form = csrf_action_form(path('save'), 'save', fields: {note: ''}, fieldTypes: {note: 'text'}, fieldOptions: {note: {attr: {maxlength: 80}}}) %}
```

| Argument | Default | Notes |
|----------|---------|-------|
| `action` | — | Form action URL |
| `csrfTokenId` | — | Token id the controller validates (`isCsrfTokenValid()` / form `isValid()`). Must not be empty (`InvalidArgumentException`) |
| `method` | `'POST'` | Upper-cased. GET / HEAD / empty throw `InvalidArgumentException` (the token would end up in the URL); use `GetFilterFormFactory` for GET filters |
| `named` | `true` | `true` → `csrf_only[_token]`; `false` → flat field. Ignored when `fields` is not empty |
| `csrfFieldName` | `'_token'` | e.g. `'_csrf_token'` for kit forks |
| `fields` | `{}` | Field name ⇒ default value (`null` ⇒ `''`); switches to `HiddenFieldsCsrfType` |
| `fieldTypes` | `{}` | Field name ⇒ FormKit snake type (default `hidden`) |
| `fieldOptions` | `{}` | Field name ⇒ Form Type options |

The function is registered when Twig is installed and `nowo_form_kit.twig.csrf_action_form` is `true` (default). Twig does not reject duplicate function names (the last registered extension wins), so an app that still defines its own `csrf_action_form` keeps working; set the option to `false` to disable the bundle function. See [Configuration](CONFIGURATION.md#twig-helpers-twig).

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

## Stateless CSRF for public forms

Since **2.7.0** (opt-in). Anonymous forms rendered on public pages should not open a session just to store a CSRF token: it costs a session write per page view and turns otherwise cacheable responses `private`.

```yaml
# config/packages/nowo_form_kit.yaml
nowo_form_kit:
    stateless_csrf:
        enabled: true
        form_types:
            - App\Form\NewsletterType
            - Nowo\BlogKitBundle\Form\PublicBlogCommentType
```

What happens:

1. For each listed type the bundle registers a `StatelessCsrfTokenIdExtension` service tagged `form.type_extension` with `extended_type: <FQCN>`; it sets the **default** `csrf_token_id` to `token_id` (default `submit`).
2. With `register_stateless_token_id: true` (default) the bundle prepends `token_id` to `framework.csrf_protection.stateless_token_ids`, so Symfony's `SameOriginCsrfTokenManager` validates it without the session (Origin/Referer + double-submit cookie). If you already list it (Symfony recipe: `[submit, authenticate, logout]`), the duplicate is harmless.
3. Explicit `csrf_token_id` options still win, so a type can opt back out per instance.

Checklist:

- Use it for **anonymous** forms only; logged-in, state-changing forms keep session-bound tokens.
- If your reverse proxy strips `Origin`/`Referer`, also install the recipe's `csrf_protection_controller.js` (double-submit cookie) or the stateless check will reject submissions.
- Check `Vary`/`Cache-Control` of public pages after enabling: no `Set-Cookie: PHPSESSID` should be emitted for a plain page view.
