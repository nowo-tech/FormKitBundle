<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Twig;

use Symfony\Component\Form\FormView;
use Throwable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_scalar;
use function is_string;
use function method_exists;
use function preg_replace;
use function str_replace;
use function strtolower;

/**
 * Twig API of the locale tabs (2.13): groups the children of a form view per locale for
 * `@NowoFormKitBundle/form/_locale_tabs.html.twig` and tells whether UiKit (2.5+) renders them.
 *
 * - `nowo_form_kit_locale_tabs(field, options)` returns
 *   `{mode, locales, default_locale, required_locales, groups, ui_kit_template}`; each group is
 *   `{locale, rows: FormView[], hidden: [{view, value}], errors: FormView[], invalid, required, dir}`.
 *   Modes:
 *   - `children` (default): one child per locale (`slugs[es]`), e.g. TranslatableMapType;
 *   - `entries` (a list of translation entities): each child is an entry whose `locale_child`
 *     (default `locale`) holds the locale; that child becomes a hidden input, the other children
 *     are the panel rows;
 *   - `fields` (`field_<locale>` groups): `fields: ['name', 'description']` with
 *     `pattern: '{field}_{locale}'` picks `name_es`, `description_es`… out of the view.
 *   Invalid leaf views of every panel get `aria-invalid="true"`.
 * - `nowo_form_kit_ui_kit_locale_tabs()` returns the UiKit `locale_tabs` partial template (theme
 *   replaceable) when UiKit 2.5+ is installed and registered, else null (neutral FormKit markup).
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class LocaleTabsTwigExtension extends AbstractExtension
{
    public const UI_KIT_TEMPLATE = '@NowoUiKitBundle/partials/_locale_tabs.html.twig';

    /** Primary language subtags written right to left (same list as UiKit's `nowo_ui_locale_dir()`). */
    public const RTL_LANGUAGES = ['ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'iw', 'khw', 'ks', 'ku', 'ps', 'sd', 'syr', 'ug', 'ur', 'yi'];

    /**
     * @param list<string> $locales configured locales (`nowo_form_kit.locale_tabs.locales`, default `kernel.enabled_locales`)
     * @param bool|null    $uiKit   null = UiKit when installed, false = never (`nowo_form_kit.locale_tabs.renderer`)
     */
    public function __construct(
        private readonly array $locales = [],
        private readonly ?string $defaultLocale = null,
        private readonly ?bool $uiKit = null,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nowo_form_kit_locale_tabs', $this->localeTabs(...), ['needs_environment' => true]),
            new TwigFunction('nowo_form_kit_ui_kit_locale_tabs', $this->uiKitTemplate(...), ['needs_environment' => true]),
        ];
    }

    /**
     * Template of the UiKit `locale_tabs` partial, null when UiKit (2.5+) is missing or disabled.
     */
    public function uiKitTemplate(Environment $env, ?bool $enabled = null): ?string
    {
        if (($enabled ?? $this->uiKit) === false) {
            return null;
        }
        $partial = $env->getFunction('nowo_ui_partial')?->getCallable();
        if (!is_callable($partial) || !$env->getLoader()->exists(self::UI_KIT_TEMPLATE)) {
            return null;
        }
        try {
            $template = $partial('locale_tabs');
        } catch (Throwable) {
            return null;
        }

        return is_string($template) && $template !== '' ? $template : null;
    }

    /**
     * @param array<string, mixed> $options locales, default_locale, required_locales, mode, fields, pattern,
     *                                      locale_child, default_child, ui_kit
     *
     * @return array{mode: string, locales: list<string>, default_locale: string, required_locales: list<string>, groups: list<array{locale: string, rows: list<FormView>, hidden: list<array{view: FormView, value: string}>, errors: list<FormView>, invalid: bool, required: bool, dir: string}>, ui_kit_template: string|null}
     */
    public function localeTabs(Environment $env, FormView $field, array $options = []): array
    {
        $fields      = $this->stringList($options['fields'] ?? null);
        $localeChild = is_string($options['locale_child'] ?? null) ? $options['locale_child'] : 'locale';
        $mode        = is_string($options['mode'] ?? null) ? $options['mode'] : ($fields !== [] ? 'fields' : ($this->isEntryList($field, $localeChild) ? 'entries' : 'children'));

        $rowsByLocale = match ($mode) {
            'entries' => $this->entryGroups($field, $localeChild),
            'fields'  => [],
            default   => $this->childGroups($field, $options['default_child'] ?? null, $options['default_locale'] ?? ($this->stringList($options['locales'] ?? null)[0] ?? null)),
        };

        $locales = $this->stringList($options['locales'] ?? null);
        if ($locales === []) {
            $locales = $mode === 'fields' ? $this->locales : array_keys($rowsByLocale);
        }
        if ($mode === 'fields') {
            $pattern      = is_string($options['pattern'] ?? null) ? $options['pattern'] : '{field}_{locale}';
            $rowsByLocale = $this->fieldGroups($field, $fields, $pattern, $locales);
        }

        $default = is_string($options['default_locale'] ?? null) && $options['default_locale'] !== '' ? $options['default_locale'] : null;
        if ($default === null && $this->defaultLocale !== null && in_array($this->defaultLocale, $locales, true)) {
            $default = $this->defaultLocale;
        }
        $default ??= $locales[0] ?? '';
        $required = $this->stringList($options['required_locales'] ?? null);

        $groups = [];
        foreach ($locales as $locale) {
            // children mode keeps an (empty) tab for a listed locale without a child, as in 2.7–2.12
            $group = $rowsByLocale[$locale] ?? ($mode === 'children' ? ['rows' => [], 'hidden' => [], 'errors' => []] : null);
            if ($group === null) {
                continue;
            }
            foreach ($group['rows'] as $row) {
                $this->markInvalid($row);
            }
            $views    = array_merge($group['rows'], $group['errors']);
            $groups[] = [
                'locale'   => $locale,
                'rows'     => $group['rows'],
                'hidden'   => $group['hidden'],
                'errors'   => $group['errors'],
                'invalid'  => array_filter($views, static fn (FormView $v): bool => ($v->vars['valid'] ?? true) === false) !== [],
                'required' => in_array($locale, $required, true),
                'dir'      => in_array(strtolower((string) preg_replace('/[-_].*$/', '', $locale)), self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr',
            ];
        }

        return [
            'mode'             => $mode,
            'locales'          => $locales,
            'default_locale'   => $default,
            'required_locales' => $required,
            'groups'           => $groups,
            'ui_kit_template'  => $this->uiKitTemplate($env, isset($options['ui_kit']) ? (bool) $options['ui_kit'] : null),
        ];
    }

    /**
     * @return array<string, array{rows: list<FormView>, hidden: list<array{view: FormView, value: string}>, errors: list<FormView>}>
     */
    private function childGroups(FormView $field, mixed $defaultChild, mixed $defaultLocale): array
    {
        $groups = [];
        foreach ($field->children as $name => $child) {
            $groups[(string) $name] = ['rows' => [$child], 'hidden' => [], 'errors' => []];
        }
        $defaultLocale = is_string($defaultLocale) && $defaultLocale !== '' ? $defaultLocale : (string) array_key_first($field->children);
        if ($defaultChild instanceof FormView && $defaultLocale !== '') {
            $groups[$defaultLocale] = ['rows' => [$defaultChild], 'hidden' => [], 'errors' => []];
        }

        return $groups;
    }

    /**
     * @return array<string, array{rows: list<FormView>, hidden: list<array{view: FormView, value: string}>, errors: list<FormView>}>
     */
    private function entryGroups(FormView $field, string $localeChild): array
    {
        $groups = [];
        foreach ($field->children as $name => $entry) {
            $locale = $this->entryLocale($entry, $localeChild) ?? (string) $name;
            $rows   = [];
            foreach ($entry->children as $childName => $child) {
                if ($childName !== $localeChild) {
                    $rows[] = $child;
                }
            }
            $hidden          = isset($entry->children[$localeChild]) ? [['view' => $entry->children[$localeChild], 'value' => $locale]] : [];
            $groups[$locale] = ['rows' => $rows, 'hidden' => $hidden, 'errors' => [$entry]];
        }

        return $groups;
    }

    /**
     * @param list<string> $fields
     * @param list<string> $locales
     *
     * @return array<string, array{rows: list<FormView>, hidden: list<array{view: FormView, value: string}>, errors: list<FormView>}>
     */
    private function fieldGroups(FormView $field, array $fields, string $pattern, array $locales): array
    {
        $groups = [];
        foreach ($locales as $locale) {
            $rows = [];
            foreach ($fields as $name) {
                $key = str_replace(['{field}', '{locale}'], [$name, $locale], $pattern);
                if (isset($field->children[$key])) {
                    $rows[] = $field->children[$key];
                }
            }
            if ($rows !== []) {
                $groups[$locale] = ['rows' => $rows, 'hidden' => [], 'errors' => []];
            }
        }

        return $groups;
    }

    private function isEntryList(FormView $field, string $localeChild): bool
    {
        if ($field->children === []) {
            return false;
        }
        foreach ($field->children as $entry) {
            if (!isset($entry->children[$localeChild])) {
                return false;
            }
        }

        return true;
    }

    private function entryLocale(FormView $entry, string $localeChild): ?string
    {
        $value = isset($entry->children[$localeChild]) ? ($entry->children[$localeChild]->vars['value'] ?? null) : null;
        if (!is_scalar($value) || (string) $value === '') {
            $data   = $entry->vars['data'] ?? null;
            $getter = 'get' . str_replace('_', '', $localeChild);
            $value  = match (true) {
                is_array($data) && array_key_exists($localeChild, $data) => $data[$localeChild],
                is_object($data) && method_exists($data, $getter)        => $data->{$getter}(),
                default                                                   => null,
            };
        }

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /** Sets `aria-invalid="true"` on every invalid leaf view (the widgets a screen reader announces). */
    private function markInvalid(FormView $view): void
    {
        if (($view->vars['compound'] ?? false) === true) {
            foreach ($view->children as $child) {
                $this->markInvalid($child);
            }

            return;
        }
        if (($view->vars['valid'] ?? true) === false) {
            $attr               = is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [];
            $view->vars['attr'] = ['aria-invalid' => 'true'] + $attr;
        }
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $v): string => is_scalar($v) ? (string) $v : '', array_filter($value, is_scalar(...)))));
    }
}
