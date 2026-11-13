# Architecture

`nowo-tech/form-kit-bundle` is a presentation bundle: form types, type extensions, Twig theming and the services that
build form options. It has no entities, no persistence and no events.

## Table of contents

- [Public ports](#public-ports)
- [UI service interfaces (presentation adapters)](#ui-service-interfaces-presentation-adapters)
- [Extension points (SPI)](#extension-points-spi)
- [Standard Symfony and DDD hosts](#standard-symfony-and-ddd-hosts)

## Public ports

| Port | Default implementation | Purpose |
| --- | --- | --- |
| `Form\FormOptionsMergerInterface` | `Form\FormOptionsMerger` | Resolves the final options of a field (profile defaults, convention keys, explicit options). |
| `Form\FormTypeMapInterface` | `Form\FormTypeMap` | Maps snake_case type names to form type classes (`FormTypeMap::NOWO_WIDGETS` stays on the class). |
| `Css\CssClassUtilitiesInterface` | `Css\CssClassUtilities` | Normalises column classes and orders utility classes for the configured CSS framework. |
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

Constants and static helpers (`CsrfProtectionGuard`,
`VisibleWhenRules`, `FormKitOptionMerger`) stay on their classes. Keep replacements stateless between requests
(worker mode, kernel reset false).

## UI service interfaces (presentation adapters)

These interfaces are UI adapters, not domain ports. They may use Symfony Form and Validator types in their signatures
(`FormInterface`, `FormFactoryInterface`, `Constraint`) because their job is to build forms and constraints. Each is
still aliased to its default service and replaceable through the alias:

- `Form\Constraint\ConstraintDefinitionFactoryInterface` (default ``Form\Constraint\ConstraintDefinitionFactory``): Builds validator constraints from configuration definitions.
- `Form\CsrfOnlyFormFactoryInterface` (default ``Form\CsrfOnlyFormFactory``): CSRF-only forms for state-changing actions.
- `Form\GetFilterFormFactoryInterface` (default ``Form\GetFilterFormFactory``): Rootless GET filter forms.
- `Form\MultiStepFormBuilderInterface` (default ``Form\MultiStepFormBuilder``): Form of one wizard step.

The ports in the table above stay free of Symfony, Doctrine and HTTP types (REQ-ARCH-001 rule 3); no exception to
rule 3 is recorded for the UI adapters, they are simply outside the port list.

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
