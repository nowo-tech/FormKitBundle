<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use function is_array;
use function is_string;
use function str_starts_with;

/**
 * Framework chrome of the `checkbox_row` block of `@NowoFormKitBundle/form/static_blocks.html.twig` (2.13), from the
 * default profile: `checkbox_bootstrap` (the profile's `css_framework`, else the root `css_framework`, is Bootstrap →
 * `form-check` / `form-check-input` / `form-check-label`) and `checkbox_row_class` (the row class when the field has
 * none: the profile's `defaults.row_attr.class`, else `mb-3` on Bootstrap, else none).
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class CheckboxRowExtension extends AbstractTypeExtension
{
    /**
     * @param array<string, array<string, mixed>> $profiles
     */
    public function __construct(
        private readonly array $profiles,
        private readonly string $defaultProfileName,
        private readonly string $cssFramework = 'bootstrap',
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [CheckboxType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $profile   = $this->profiles[$this->defaultProfileName] ?? [];
        $framework = is_string($profile['css_framework'] ?? null) && $profile['css_framework'] !== '' ? $profile['css_framework'] : $this->cssFramework;
        $bootstrap = str_starts_with($framework, 'bootstrap');
        $defaults  = is_array($profile['defaults'] ?? null) ? $profile['defaults'] : [];
        $rowAttr   = is_array($defaults['row_attr'] ?? null) ? $defaults['row_attr'] : [];
        $rowClass  = is_string($rowAttr['class'] ?? null) ? $rowAttr['class'] : null;

        $view->vars['checkbox_bootstrap'] = $bootstrap;
        $view->vars['checkbox_row_class'] = $rowClass ?? ($bootstrap ? 'mb-3' : '');
    }
}
