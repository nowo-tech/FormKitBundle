# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/form-kit-bundle`  
**Last audited**: 2026-11-27  
**Audit command**: `find src -type f ! -name '*.test.ts' | wc -l` (109 production units; see [Coverage summary](#coverage-summary))

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

## Ports (interfaces)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Css/CssClassUtilitiesInterface.php` | Class utilities contract | FR-CSS-001 |
| `Form/FormOptionsMergerInterface.php` | Merge service contract | FR-FORM-002 |
| `Form/FormTypeMapInterface.php` | Type map contract | FR-FORM-004 |
| `Form/Constraint/ConstraintDefinitionFactoryInterface.php` | Constraint factory contract | FR-FORM-007 |
| `Form/CsrfOnlyFormFactoryInterface.php` | CSRF-only factory contract | FR-FORM-016 |
| `Form/GetFilterFormFactoryInterface.php` | Rootless GET factory contract | FR-FORM-017 |
| `Form/MultiStepFormBuilderInterface.php` | Wizard builder contract | FR-WIZ-001 |
| `Form/MultiStepWizardSessionInterface.php` | Wizard session contract | FR-WIZ-002 |
| `Form/MultiStepWizardSessionFactoryInterface.php` | Wizard session factory contract | FR-WIZ-001, FR-WIZ-002 |

## Field options and widgets

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Form/Extension/VisibleWhenExtension.php` | `visible_when` option | FR-FORM-020 |
| `Form/VisibleWhenRules.php` | Server twin of the `visible-when` peer (hidden fields keep their value) | FR-FORM-020 |
| `Form/Extension/ImageCropExtension.php` | `crop` option on file inputs | FR-FORM-021 |
| `Form/ImageCropAttributes.php` | UiKit image-crop attribute contract | FR-FORM-021 |
| `Form/Extension/ChoiceCardsExtension.php` | `choice_cards` option ([spec 004](../004-field-widgets/spec.md)) | FR-FORM-022 |
| `Form/Extension/SwitchExtension.php` | `switch` option ([spec 004](../004-field-widgets/spec.md)) | FR-FORM-022 |
| `Form/Extension/CharCounterExtension.php` | `char_counter` option ([spec 004](../004-field-widgets/spec.md)) | FR-FORM-022 |
| `Form/Extension/UiWidgetExtension.php` | `ui_widget` option (`option_cards`) on `ChoiceType` (2.24) | FR-FORM-023 |
| `Resources/views/form/field_widgets_theme.html.twig` | Field widgets form theme | FR-FORM-022 |
| `Resources/views/form/choice_blocks.html.twig` | Choice cards blocks | FR-FORM-022 |
| `Resources/views/form/_option_cards_field.html.twig` | Expanded choice drawn with UiKit's `option_cards` partial (2.24); neutral cards without UiKit 2.64+ | FR-FORM-023 |
| `Resources/public/field-widgets.css` | Field widgets styles | FR-FORM-022 |

## Help modal nonce

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `HelpModal/HelpModalNonceInterface.php` | Nonce contract | FR-SEC-002 |
| `HelpModal/HelpModalNonce.php` | Per-request nonce (request attributes, worker-safe) | FR-SEC-002 |
| `HelpModal/HelpModalNonceResponseListener.php` | `Vary` / `Cache-Control: private` when the nonce came from the header | FR-SEC-002 |
| `Twig/HelpModalTwigExtension.php` | `nowo_form_kit_help_modal_meta()` | FR-SEC-002, FR-TWIG-001 |

## Frontend assets

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/assets/src/help-modal.ts` | Help modal script (Vite entry) | FR-ASSET-001 |
| `Resources/assets/src/logger.ts` | Frontend logger: `scriptLoaded()` banner with build label, quiet switch (`data-nowo-console`, `NOWO_CONSOLE`), debug-gated levels (REQ-ASSETS-005) | FR-ASSET-001, FR-ASSET-004 |
| `Resources/assets/src/build-info.d.ts` | Ambient TypeScript declarations for the Vite imports `*?raw` and `*build-info.json` (`{ sourceHash, builtAt }`) used by the build-label injection; type-only, emits no code | FR-ASSET-004, FR-BUILD-001 |
| `Resources/public/help-modal.js` | **Build output** of `help-modal.ts` + `logger.ts` (Vite IIFE, `vite.config.ts`) | FR-BUILD-001 |
| `Resources/public/help-modal.css` | Help modal styles (hand-written, shipped as is) | FR-ASSET-001 |
| `Resources/public/locale-tabs.css` | Optional locale tabs styles | FR-FORM-013 |
| `Resources/assets/stimulus-peers/combobox_controller.ts` | Opt-in combobox Stimulus peer | FR-ASSET-003 |
| `Resources/assets/stimulus-peers/motion.ts` | Closing effect shared by the combobox peer ([spec 003](../003-combobox-peer/spec.md)) | FR-ASSET-003 |
| `Resources/public/combobox.css` | Optional combobox list motion ([spec 003](../003-combobox-peer/spec.md)) | FR-ASSET-003 |
| `Resources/assets/enhancers/char-counter.ts` | Character counter enhancer ([spec 004](../004-field-widgets/spec.md)) | FR-FORM-022 |
| `Resources/assets/enhancers/char-counter-entry.ts` | Vite entry of the counter; logs the `form-kit/char-counter` script-loaded banner | FR-FORM-022, FR-ASSET-004 |
| `Resources/public/char-counter.js` | **Build output** of the counter | FR-BUILD-001 |

Co-located Vitest files are **excluded** from the inventory (test code, not shipped behaviour; they are listed only for traceability):
`Resources/assets/src/help-modal.test.ts` (FR-ASSET-001), `Resources/assets/src/logger.test.ts` (FR-ASSET-001),
`Resources/assets/stimulus-peers/combobox_controller.test.ts` (FR-ASSET-003), `Resources/assets/stimulus-peers/motion.test.ts` (FR-ASSET-003),
`Resources/assets/enhancers/char-counter.test.ts` (FR-FORM-022), `Resources/assets/enhancers/script-loaded.test.ts`, `Resources/assets/src/build-info.test.ts` and `Resources/assets/src/logger-quiet.test.ts` (FR-ASSET-004).

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
| `Twig/UiKitPartialTwigExtension.php` | `nowo_form_kit_ui_partial(name)`: UiKit theme partial template or null (2.24) | FR-TWIG-004, FR-FORM-023 |

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
| Ports (interfaces) | 9 | 9 |
| Field options and widgets | 12 | 12 |
| Help modal nonce | 4 | 4 |
| Frontend assets (incl. 2 build outputs) | 12 | 12 |
| Translations | 7 | 7 |
| Twig extensions | 3 | 3 |
| Twig views | 9 | 9 |
| **Total production sources** | **109** | **109** |

Excluded from the total: 8 co-located Vitest files (`*.test.ts`), so `find src -type f | wc -l` = 117 = 109 + 8.
