# Architecture

`nowo-tech/form-kit-bundle` is a presentation bundle: form types, type extensions, Twig theming and the services that
build form options. It has no entities, no persistence and no events.

## Table of contents

- [Public ports](#public-ports)
- [Extension points (SPI)](#extension-points-spi)
- [Standard Symfony and DDD hosts](#standard-symfony-and-ddd-hosts)

## Public ports

| Port | Default implementation | Purpose |
| --- | --- | --- |
| `Form\FormOptionsMergerInterface` | `Form\FormOptionsMerger` | Resolves the final options of a field (profile defaults, convention keys, explicit options). |
| `Form\FormTypeMapInterface` | `Form\FormTypeMap` | Maps snake_case type names to form type classes (`FormTypeMap::NOWO_WIDGETS` stays on the class). |
| `Form\Constraint\ConstraintDefinitionFactoryInterface` | `Form\Constraint\ConstraintDefinitionFactory` | Builds validator constraints from configuration definitions. |
| `Css\CssClassUtilitiesInterface` | `Css\CssClassUtilities` | Normalises column classes and orders utility classes for the configured CSS framework. |
| `Form\CsrfOnlyFormFactoryInterface` | `Form\CsrfOnlyFormFactory` | CSRF-only forms for state-changing actions. |
| `Form\GetFilterFormFactoryInterface` | `Form\GetFilterFormFactory` | Rootless GET filter forms. |
| `Form\MultiStepFormBuilderInterface` | `Form\MultiStepFormBuilder` | Form of one wizard step. |
| `Form\MultiStepWizardSessionFactoryInterface` | `Form\MultiStepWizardSessionFactory` | Creates `MultiStepWizardSessionInterface` instances (session-backed by default). |
| `HelpModal\HelpModalNonceInterface` | `HelpModal\HelpModalNonce` | Per-request nonce that binds help-modal payloads to the page. |
| `Form\DataTransformer\DataTransformer` | `Bool/Csv/Json/Money/SwitchModelTransformer` | Model transformers for value objects. |

Each interface is aliased to its default service and the old class ids still resolve. The form base types
(`FormKitAbstractType`, `AbstractFormKitType`), the three traits (`FormKitTrait`, `FormOptionsTrait`,
`FormKitControllerTrait`), the multi-step builder, the Twig extensions and the help-modal extension type-hint the
interfaces, so one alias replaces the service everywhere:

```yaml
services:
  App\Ui\TenantFormOptionsMerger: ~
  Nowo\FormKitBundle\Form\FormOptionsMergerInterface:
    alias: App\Ui\TenantFormOptionsMerger
```

The form-oriented ports use Symfony Form types (`FormInterface`, `FormFactoryInterface`) because their job is to build
Symfony forms; a presentation bundle is expected to. Constants and static helpers (`CsrfProtectionGuard`,
`VisibleWhenRules`, `FormKitOptionMerger`) stay on their classes. Keep replacements stateless between requests
(worker mode, kernel reset false).

## Extension points (SPI)

- Profiles and `#[FormKitConfig]`: per-profile defaults, convention keys, field types and per-form overrides.
- `FormTypeMapInterface` / `nowo_form_kit.type_map`: your own snake_case type names.
- Model transformers: implement `Form\DataTransformer\DataTransformer`.
- Twig theming: override the `@NowoFormKitBundle` templates and blocks (the extension point for presentation bundles).
- The service ports above.

## Standard Symfony and DDD hosts

- **Standard Symfony host:** bind form types to entities with `data_class`, use the traits and the controller trait, and
  keep the defaults; every port is autowirable by interface.
- **DDD / hexagonal host:** keep FormKit in the UI layer. Bind forms to input DTOs or commands (no form type needs a
  `data_class`), turn value objects into model transformers, map the submitted DTO to a command in the controller or an
  application service, and swap any service through its interface alias. See
  [USAGE.md](USAGE.md#using-it-from-a-ddd--hexagonal-application) and `tests/Integration/Form/HostPathsFormTest.php`.
