# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/form-kit-bundle`  
**Last audited**: 2026-07-07

## Symfony config

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/services.yaml` | Service wiring | FR-DI-001 |

## Bundle & DI

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoFormKitBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/FormKitExtension.php` | DI extension | FR-CFG-002 |
| `DependencyInjection/FormOptionsMergerInjectorCompilerPass.php` | Merger injection | FR-DI-002 |

## CSS framework utilities

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Css/CssFramework.php` | Framework enum | FR-CSS-001 |
| `Css/CssClassUtilities.php` | Class utilities contract | FR-CSS-001 |
| `Css/BootstrapCssClassUtilities.php` | Bootstrap column merge | FR-CSS-001 |
| `Css/TailwindCssClassUtilities.php` | Tailwind column merge | FR-CSS-001 |
| `Css/FoundationCssClassUtilities.php` | Foundation column merge | FR-CSS-001 |
| `Css/NullCssClassUtilities.php` | No-op utilities | FR-CSS-001 |

## Controller trait

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Controller/FormKitControllerTrait.php` | Controller field helpers | FR-CTRL-001 |

## Form — core API

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/AbstractFormKitType.php` | Base form type | FR-FORM-001 |
| `Form/AbstractFormKitWrappedType.php` | Wrapped type base | FR-FORM-001 |
| `Form/FormKitAbstractType.php` | Snake_case type base | FR-FORM-001 |
| `Form/FormKitTrait.php` | Type-map trait | FR-FORM-001 |
| `Form/FormOptionsTrait.php` | FQCN helper trait | FR-FORM-001 |
| `Form/FormOptionsMerger.php` | Cascading merge service | FR-FORM-002 |
| `Form/FormKitOptionMerger.php` | Kit-specific merge | FR-FORM-002 |
| `Form/FormFieldOptionsHelper.php` | Label/placeholder/help keys | FR-FORM-003 |
| `Form/FormTypeMap.php` | Built-in + config type map | FR-FORM-004 |

## Form — extensions

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/Extension/InputGroupExtension.php` | Input group prefix/suffix | FR-FORM-005 |
| `Form/Extension/RequiredLabelSuffixExtension.php` | Required label suffix | FR-FORM-005 |
| `Form/Extension/HelpModalExtension.php` | Help modal option | FR-FORM-005 |

## Form — data transformers

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/DataTransformer/DataTransformer.php` | Transformer contract | FR-FORM-006 |
| `Form/DataTransformer/SwitchModelTransformer.php` | Switch bool mapping | FR-FORM-006 |
| `Form/DataTransformer/JsonModelTransformer.php` | JSON string mapping | FR-FORM-006 |
| `Form/DataTransformer/BoolModelTransformer.php` | Bool mapping | FR-FORM-006 |
| `Form/DataTransformer/MoneyModelTransformer.php` | Money mapping | FR-FORM-006 |
| `Form/DataTransformer/CsvModelTransformer.php` | CSV list mapping | FR-FORM-006 |

## Form — constraints

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/Constraint/ConstraintDefinitionFactory.php` | Constraint from config | FR-FORM-007 |

## Form — static & translation types

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/Type/StaticAlertType.php` | Static alert block | FR-FORM-008 |
| `Form/Type/StaticHtmlType.php` | Static HTML block | FR-FORM-008 |
| `Form/Type/StaticSeparatorType.php` | Static separator | FR-FORM-008 |
| `Form/Type/TranslationsFormsType.php` | A2lix translations type | FR-FORM-008 |

## Form — multi-step wizard

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/MultiStepFormBuilder.php` | Wizard builder | FR-WIZ-001 |
| `Form/MultiStepWizardSession.php` | Session state | FR-WIZ-001 |
| `Form/MultiStepWizardSessionFactory.php` | Session factory | FR-WIZ-001 |

## Frontend assets

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/assets/src/help-modal.ts` | Help modal script | FR-ASSET-001 |
| `Resources/assets/src/help-modal.test.ts` | Help modal tests | FR-ASSET-001 |
| `Resources/assets/src/logger.ts` | Frontend logger | FR-ASSET-001 |
| `Resources/assets/src/logger.test.ts` | Logger tests | FR-ASSET-001 |
| `Resources/public/help-modal.js` | Built help modal | FR-ASSET-001 |
| `Resources/public/help-modal.css` | Help modal styles | FR-ASSET-001 |

## Twig views

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/views/components/form_renderer.html.twig` | Form renderer | FR-TWIG-001 |
| `Resources/views/form/static_blocks.html.twig` | Static block theme | FR-TWIG-001 |
| `Resources/views/help_modal/shells.html.twig` | Modal shell include | FR-TWIG-001 |
| `Resources/views/help_modal/shell_bootstrap4.html.twig` | Bootstrap 4 shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_bootstrap5.html.twig` | Bootstrap 5 shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_foundation.html.twig` | Foundation shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_tailwind.html.twig` | Tailwind shell | FR-TWIG-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Symfony config | 1 | 1 |
| Bundle & DI | 4 | 4 |
| CSS framework utilities | 6 | 6 |
| Controller trait | 1 | 1 |
| Form — core API | 9 | 9 |
| Form — extensions | 3 | 3 |
| Form — data transformers | 6 | 6 |
| Form — constraints | 1 | 1 |
| Form — static & translation types | 4 | 4 |
| Form — multi-step wizard | 3 | 3 |
| Frontend assets | 6 | 6 |
| Twig views | 7 | 7 |
| **Total production sources** | **51** | **51** |
