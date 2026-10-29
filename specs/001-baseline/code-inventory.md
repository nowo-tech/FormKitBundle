# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/form-kit-bundle`  
**Last audited**: 2026-10-29  
**Audit command**: `find src -type f ! -name '*.test.ts' | wc -l` (77 production units; see [Coverage summary](#coverage-summary))

## Symfony config

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/services.yaml` | Service wiring | FR-DI-001 |

## Bundle & DI

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoFormKitBundle.php` | Bundle entry | FR-BUNDLE-001 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/FormKitExtension.php` | DI extension (+ `nowo_form_kit` asset package prepend) | FR-CFG-002, FR-ASSET-002 |
| `DependencyInjection/Compiler/TwigPathsPass.php` | Twig namespace paths | FR-BUNDLE-001 |
| `DependencyInjection/FormOptionsMergerInjectorCompilerPass.php` | Merger injection | FR-DI-002 |
| `DependencyInjection/Compiler/LocaleTabsLocalesPass.php` | Locale tabs defaults from `kernel.enabled_locales` / `kernel.default_locale` | FR-DI-003, FR-FORM-013 |

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
| `Controller/FormKitControllerTrait.php` | Controller field helpers; optional nowo-tech `*FieldType` | FR-CTRL-001, FR-FORM-012 |

## Form — core API

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/AbstractFormKitType.php` | Base form type | FR-FORM-001 |
| `Form/AbstractFormKitWrappedType.php` | Wrapped type base | FR-FORM-001 |
| `Form/FormKitAbstractType.php` | Snake_case type base | FR-FORM-001 |
| `Form/FormKitTrait.php` | Type-map trait; `withBuilder` / `add*Field` / `addNamedField` / `#[FormKitConfig]`; optional nowo-tech helpers | FR-FORM-001, FR-FORM-009, FR-FORM-012 |
| `Form/FormOptionsTrait.php` | FQCN helper trait; `withBuilder` / `add*Field` / `resolveFieldOptions` / `#[FormKitConfig]`; optional nowo-tech helpers | FR-FORM-001, FR-FORM-009, FR-FORM-010, FR-FORM-012 |
| `Attribute/FormKitConfig.php` | Named config attribute | FR-FORM-011 |
| `Form/FormOptionsMerger.php` | Cascading merge service | FR-FORM-002 |
| `Form/FormKitOptionMerger.php` | Kit-specific merge | FR-FORM-002 |
| `Form/FormFieldOptionsHelper.php` | Label/placeholder/help keys | FR-FORM-003 |
| `Form/FormTypeMap.php` | Built-in + config type map; optional nowo-tech snake_case entries | FR-FORM-004, FR-FORM-012 |

## Form — CSRF action and GET filter forms

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/CsrfOnlyFormFactory.php` | CSRF-only form factory (`create` / `createNamed` / `createWithFields`) | FR-FORM-016 |
| `Form/CsrfProtectionGuard.php` | Fail-closed guard when form CSRF is unavailable | FR-FORM-016, FR-SEC-001 |
| `Form/Type/CsrfOnlyType.php` | CSRF-only action form type | FR-FORM-016 |
| `Form/Type/HiddenFieldsCsrfType.php` | CSRF action form with typed (hidden by default) fields | FR-FORM-016 |
| `Form/AbstractGetFilterType.php` | Base type for rootless GET filters (`filter` profile) | FR-FORM-017 |
| `Form/GetFilterFormFactory.php` | Rootless GET form factory | FR-FORM-017 |
| `Form/Type/SearchQueryType.php` | `q` search filter form | FR-FORM-017 |

## Form — extensions

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/Extension/InputGroupExtension.php` | Input group prefix/suffix | FR-FORM-005 |
| `Form/Extension/RequiredLabelSuffixExtension.php` | Required label suffix | FR-FORM-005 |
| `Form/Extension/HelpModalExtension.php` | Help modal option | FR-FORM-005 |
| `Form/Extension/LocaleTabsExtension.php` | `locale_tabs` option + block prefix | FR-FORM-013 |
| `Form/Extension/StatelessCsrfTokenIdExtension.php` | Opt-in stateless `csrf_token_id` default | FR-FORM-014 |
| `Form/Extension/UnlabelledChoiceAriaLabelExtension.php` | `aria-label` for label-less selects (help → placeholder) | FR-FORM-015 |
| `Form/Extension/CheckboxRowExtension.php` | `checkbox_row` framework chrome from the default profile | FR-FORM-018 |

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
| `Form/Type/TranslatableMapType.php` | `{locale: text}` map edited as one field per locale (locale tabs) | FR-FORM-019, FR-FORM-013 |

## Form — multi-step wizard

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/MultiStepFormBuilder.php` | Wizard builder | FR-WIZ-001 |
| `Form/MultiStepWizardSession.php` | Session state | FR-WIZ-001 |
| `Form/MultiStepWizardSessionFactory.php` | Session factory | FR-WIZ-001 |

## Frontend assets

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/assets/src/help-modal.ts` | Help modal script (Vite entry) | FR-ASSET-001 |
| `Resources/assets/src/logger.ts` | Frontend logger | FR-ASSET-001 |
| `Resources/public/help-modal.js` | **Build output** of `help-modal.ts` + `logger.ts` (Vite IIFE, `vite.config.ts`) | FR-BUILD-001 |
| `Resources/public/help-modal.css` | Help modal styles (hand-written, shipped as is) | FR-ASSET-001 |
| `Resources/public/locale-tabs.css` | Optional locale tabs styles | FR-FORM-013 |
| `Resources/assets/stimulus-peers/combobox_controller.ts` | Opt-in combobox Stimulus peer | FR-ASSET-003 |

Co-located Vitest files are **excluded** from the inventory (test code, not shipped behaviour; they are listed only for traceability):
`Resources/assets/src/help-modal.test.ts` (FR-ASSET-001), `Resources/assets/src/logger.test.ts` (FR-ASSET-001),
`Resources/assets/stimulus-peers/combobox_controller.test.ts` (FR-ASSET-003).

## Translations

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/translations/NowoFormKitBundle.en.yaml` | Bundle messages, reference locale | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.es.yaml` | Bundle messages | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.fr.yaml` | Bundle messages | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.de.yaml` | Bundle messages | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.it.yaml` | Bundle messages | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.pt.yaml` | Bundle messages | FR-I18N-001 |
| `Resources/translations/NowoFormKitBundle.nl.yaml` | Bundle messages | FR-I18N-001 |

## Twig extensions

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Twig/CsrfActionFormExtension.php` | `csrf_action_form()` Twig function | FR-TWIG-002 |
| `Twig/LocaleTabsTwigExtension.php` | `nowo_form_kit_locale_tabs()` / `nowo_form_kit_ui_kit_locale_tabs()` | FR-TWIG-003, FR-FORM-013 |

## Twig views

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/views/components/form_renderer.html.twig` | Form renderer | FR-TWIG-001 |
| `Resources/views/form/static_blocks.html.twig` | Static block theme | FR-TWIG-001 |
| `Resources/views/form/_locale_tabs.html.twig` | Per-locale tabs partial | FR-FORM-013 |
| `Resources/views/form/locale_tabs_theme.html.twig` | Per-locale tabs form theme | FR-FORM-013 |
| `Resources/views/help_modal/shells.html.twig` | Modal shell include | FR-TWIG-001 |
| `Resources/views/help_modal/shell_bootstrap4.html.twig` | Bootstrap 4 shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_bootstrap5.html.twig` | Bootstrap 5 shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_foundation.html.twig` | Foundation shell | FR-TWIG-001 |
| `Resources/views/help_modal/shell_tailwind.html.twig` | Tailwind shell | FR-TWIG-001 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Symfony config | 1 | 1 |
| Bundle & DI | 6 | 6 |
| CSS framework utilities | 6 | 6 |
| Controller trait | 1 | 1 |
| Form — core API | 10 | 10 |
| Form — CSRF action and GET filter forms | 7 | 7 |
| Form — extensions | 7 | 7 |
| Form — data transformers | 6 | 6 |
| Form — constraints | 1 | 1 |
| Form — static & translation types | 5 | 5 |
| Form — multi-step wizard | 3 | 3 |
| Frontend assets (incl. 1 build output) | 6 | 6 |
| Translations | 7 | 7 |
| Twig extensions | 2 | 2 |
| Twig views | 9 | 9 |
| **Total production sources** | **77** | **77** |

Excluded from the total: 3 co-located Vitest files (`*.test.ts`), so `find src -type f | wc -l` = 80 = 77 + 3.
