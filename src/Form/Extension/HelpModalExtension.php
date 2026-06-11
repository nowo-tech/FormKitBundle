<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_replace_recursive;
use function is_array;
use function is_callable;
use function is_string;
use function json_encode;
use function spl_object_hash;
use Throwable;

/**
 * Adds "help modal" support to any form field by injecting data attributes into the rendered label.
 *
 * The frontend JS (help-modal.js built with Vite+TS) scans for:
 *   label[data-nowo-help-modal]
 * and will insert an icon and open the corresponding modal.
 *
 * Modal shell markup (Bootstrap 4/5, Tailwind, Foundation) is loaded from Twig:
 *   {% include '@NowoFormKit/help_modal/shells.html.twig' %}
 * before the script. Override per framework in your app under
 * templates/bundles/NowoFormKitBundle/help_modal/shell_*.html.twig.
 * If templates are omitted, the script falls back to built-in HTML.
 */
final class HelpModalExtension extends AbstractTypeExtension
{
    /**
     * @param array<string, array<string, mixed>> $configs     Merged Form Kit configuration (all named configs).
     * @param string                               $defaultConfigName Key used when resolving `help_modal` defaults.
     * @param object|null                          $iconRenderer      Optional; Symfony UX Icons renderer when the package is installed.
     */
    public function __construct(
        private readonly array $configs,
        private readonly string $defaultConfigName,
        private readonly ?object $iconRenderer = null,
    ) {
    }

    /**
     * @return iterable<int, class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        // Must extend FormType (not FormTypeInterface) so Symfony applies this to TextType, ChoiceType, etc.
        return [FormType::class];
    }

    /**
     * Declares the optional `help_modal` field option: boolean (use defaults), array (merge), or null/false (disabled).
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'help_modal' => null,
        ]);

        $resolver->setAllowedTypes('help_modal', ['array', 'bool', 'null']);
    }

    /**
     * Merges YAML defaults with per-field options and sets `label_attr['data-nowo-help-modal']` to a JSON payload
     * consumed by the help-modal script.
     *
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $helpModal = $options['help_modal'] ?? null;

        if ($helpModal === null || $helpModal === false) {
            return;
        }

        if ($helpModal === true) {
            $helpModal = [];
        }

        if (!is_array($helpModal)) {
            return;
        }

        $default = $this->configs[$this->defaultConfigName]['help_modal'] ?? [];
        $merged  = array_replace_recursive($default, $helpModal);

        $framework = (string) ($merged['framework'] ?? 'bootstrap5');
        $fallbackIconHtml = (string) ($merged['icon_html'] ?? '<span class="nowo-help-modal-icon" aria-hidden="true">?</span>');

        $uxIcon = $merged['ux_icon'] ?? null;
        $uxAttrs = isset($merged['ux_icon_attributes']) && is_array($merged['ux_icon_attributes'])
            ? $merged['ux_icon_attributes']
            : [];
        /** @var array<string, string|bool> $uxAttrs */

        $iconHtml = is_string($uxIcon) && $uxIcon !== ''
            ? $this->renderUxIconHtml($uxIcon, $uxAttrs, $fallbackIconHtml)
            : $fallbackIconHtml;

        $title = isset($merged['title']) ? (string) $merged['title'] : null;
        /** @var string|null $titleHtml Raw HTML for modal title (optional); plain $title is escaped on the client if this is null */
        $titleHtml = isset($merged['title_html']) && is_string($merged['title_html']) ? $merged['title_html'] : null;

        $content = isset($merged['content']) ? $merged['content'] : null;

        $triggerClass = (string) ($merged['trigger_class'] ?? 'nowo-help-modal-trigger nowo-help-modal-trigger--circle');

        $ariaLabel = isset($merged['aria_label']) && is_string($merged['aria_label']) ? $merged['aria_label'] : null;

        $modalId = isset($merged['id']) && is_string($merged['id'])
            ? $merged['id']
            : 'nowo-help-modal-' . (string) ($view->vars['id'] ?? spl_object_hash($form));

        if (is_string($content)) {
            // JSON needs explicit string value.
            $contentValue = $content;
        } else {
            $contentValue = '';
        }

        $labelAttr = $view->vars['label_attr'] ?? [];
        if (!is_array($labelAttr)) {
            $labelAttr = [];
        }

        $labelAttr['data-nowo-help-modal'] = json_encode(
            [
                'id' => $modalId,
                'framework' => $framework,
                'icon_html' => $iconHtml,
                'trigger_class' => $triggerClass,
                'title' => $title,
                'title_html' => $titleHtml,
                'content' => $contentValue,
                'aria_label' => $ariaLabel,
            ],
            JSON_UNESCAPED_UNICODE,
        );

        $view->vars['label_attr'] = $labelAttr;
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    private function renderUxIconHtml(string $name, array $attributes, string $fallbackHtml): string
    {
        $renderer = $this->iconRenderer;
        if ($renderer === null || !is_callable([$renderer, 'renderIcon'])) {
            return $fallbackHtml;
        }

        try {
            /** @var callable(string, array<string, string|bool>): string $render */
            $render = [$renderer, 'renderIcon'];

            return $render($name, $attributes);
        } catch (Throwable) {
            return $fallbackHtml;
        }
    }
}

