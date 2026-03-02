# Form Kit Bundle

Symfony bundle to reduce repetitive form field options: convention-based translation keys (`form_snake.field_snake.label`, `.placeholder`, `.help`), configurable defaults and multiple configs via YAML, and cascading option merge (global → field type → form → field).

**Compatible with Symfony 6.4, 7.x and 8.x** (PHP 8.1+).

## Features

- **Convention-based labels, placeholder and help:** Default translation keys are `{form_snake}.{field_snake}.label`, `.placeholder`, `.help`. Set any to `false` in field options to disable.
- **Multiple configs:** Define named configs (e.g. `default`, `bootstrap`) with `translation_domain`, `defaults.attr`, `defaults.row_attr`, and per-field-type options. Choose the active config per form via `setFormKitConfigName()`.
- **Cascading merge:** Options are merged in order: config defaults → field type → field options. Explicit field options override.
- **Trait or base class:** Use **FormOptionsTrait** with **FormOptionsMerger** (inject via service), or extend **FormKitAbstractType** for snake_case type names with **FormTypeMap** (same option-merging model via FormOptionsMerger).
- **Phase 2 helpers:** `addText()`, `addEmail()`, `addTextarea()`, `addPassword()`, `addUrl()`, `addInteger()`, `addNumber()`, `addCheckbox()`, `addChoice()` — pass only field name and options.
- **Build from array:** `buildFormFromArray($builder, $fields)` — define all fields in one array (type as FQCN with FormOptionsTrait, or snake_case with FormKitTrait).
- **Optional types:** Built-in type map includes optional Symfony UX types (e.g. Dropzone, Cropper) and A2lix Translations when the corresponding package is installed. Extend via `type_map` in config.

## Installation

```bash
composer require nowo-tech/form-kit-bundle
```

With Flex, the recipe creates `config/packages/nowo_form_kit.yaml`. Otherwise register the bundle in `config/bundles.php` and add the config file manually. See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Quick usage

1. **Configure** (optional) — edit `config/packages/nowo_form_kit.yaml`: set `default_config`, `configs` (each with `alias`, `translation_domain`, `defaults`, `field_types`), and optionally `type_map` for custom or UX types.

2. **Register your form as a service** and inject **FormOptionsMerger**:

```yaml
# config/services.yaml
App\Form\UserProfileType:
    tags: ['form.type']
    calls:
        - setFormOptionsMerger: ['@Nowo\FormKitBundle\Form\FormOptionsMerger']
```

3. **Use the trait** in your form type (Phase 2 or array):

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
        // Or: $this->buildFormFromArray($builder, ['full_name' => TextType::class, 'email_address' => EmailType::class]);
    }
}
```

4. **Add translations** for keys like `user_profile.full_name.label`, `user_profile.full_name.placeholder`, `user_profile.full_name.help` in your translation domain.

## Demos

The bundle includes demos (Symfony 6, 7, 8) with:

- FormType example (all field types, built from array).
- Form built in the controller using `FormOptionsMerger::resolve()`.
- **Search form** — inline/horizontal layout (search bar).
- **Example form** — card/stacked layout.

Run a demo via Docker/Make from the bundle root; see [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md).

For CSS frameworks, see [docs/USAGE.md](docs/USAGE.md) for ready-to-use configuration examples for **Bootstrap 5** and **Tailwind CSS**.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Changelog](docs/CHANGELOG.md)
- [Roadmap](docs/ROADMAP.md)
- [Upgrading](docs/UPGRADING.md)
- [Release process](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Contributing](docs/CONTRIBUTING.md)

## License

MIT. See [LICENSE](LICENSE).
