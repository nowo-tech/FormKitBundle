<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

use function array_replace_recursive;
use function is_array;
use function is_callable;
use function is_string;
use function json_encode;
use function spl_object_hash;

use const JSON_UNESCAPED_UNICODE;

/**
 * Adds "help modal" support to any form field by injecting data attributes into the rendered label.
 *
 * The frontend JS (help-modal.js built with Vite+TS) scans for:
 *   label[data-nowo-help-modal]
 * and will insert an icon and open the corresponding modal.
 *
 * Modal shell markup (Bootstrap 4/5, Tailwind, Foundation) is loaded from Twig:
 *   {% include '@NowoFormKitBundle/help_modal/shells.html.twig' %}
 * before the script. Override per framework in your app under
 * templates/bundles/NowoFormKitBundle/help_modal/shell_*.html.twig.
 * If templates are omitted, the script falls back to built-in HTML.
 *
 * Security (REQ-SEC-004): every payload carries the per-request {@see HelpModalNonce}; the script ignores payloads
 * whose nonce differs from `{{ nowo_form_kit_help_modal_meta() }}` in `<head>`. `title` and `content` are plain
 * text (escaped by the script); `title_html`, `content_html` and `icon_html` are trusted developer HTML.
 */
final class HelpModalExtension extends AbstractTypeExtension
{
    /**
     * @param array<string, array<string, mixed>> $profiles merged Form Kit configuration (all named profiles)
     * @param string $defaultProfileName key used when resolving `help_modal` defaults
     * @param object|null $iconRenderer optional; Symfony UX Icons renderer when the package is installed
     * @param HelpModalNonce $nonce per-request nonce binding payloads to the page (`nowo_form_kit_help_modal_meta()`)
     */
    public function __construct(
        private readonly array $profiles,
        private readonly string $defaultProfileName,
        private readonly ?object $iconRenderer = null,
        private readonly HelpModalNonce $nonce = new HelpModalNonce(),
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

        $default = $this->profiles[$this->defaultProfileName]['help_modal'] ?? [];
        $merged  = array_replace_recursive($default, $helpModal);

        $framework        = (string) ($merged['framework'] ?? 'bootstrap5');
        $fallbackIconHtml = (string) ($merged['icon_html'] ?? '<span class="nowo-help-modal-icon" aria-hidden="true">?</span>');

        $uxIcon  = $merged['ux_icon'] ?? null;
        $uxAttrs = isset($merged['ux_icon_attributes']) && is_array($merged['ux_icon_attributes'])
            ? $merged['ux_icon_attributes']
            : [];
        /** @var array<string, bool|string> $uxAttrs */
        $iconHtml = is_string($uxIcon) && $uxIcon !== ''
            ? $this->renderUxIconHtml($uxIcon, $uxAttrs, $fallbackIconHtml)
            : $fallbackIconHtml;

        $title = isset($merged['title']) ? (string) $merged['title'] : null;
        /** @var string|null $titleHtml Raw HTML for modal title (optional); plain $title is escaped on the client if this is null */
        $titleHtml = isset($merged['title_html']) && is_string($merged['title_html']) ? $merged['title_html'] : null;

        $content = $merged['content'] ?? null;
        /** @var string|null $contentHtml Raw HTML for the modal body (optional); plain $content is escaped on the client if this is null */
        $contentHtml = isset($merged['content_html']) && is_string($merged['content_html']) ? $merged['content_html'] : null;

        $triggerClass = (string) ($merged['trigger_class'] ?? 'nowo-help-modal-trigger nowo-help-modal-trigger--circle');

        $ariaLabel = isset($merged['aria_label']) && is_string($merged['aria_label']) ? $merged['aria_label'] : null;

        $modalId = isset($merged['id']) && is_string($merged['id'])
            ? $merged['id']
            : 'nowo-help-modal-' . ($view->vars['id'] ?? spl_object_hash($form));

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
                'id'            => $modalId,
                'framework'     => $framework,
                'icon_html'     => $iconHtml,
                'trigger_class' => $triggerClass,
                'title'         => $title,
                'title_html'    => $titleHtml,
                'content'       => $contentValue,
                'content_html'  => $contentHtml,
                'aria_label'    => $ariaLabel,
                'nonce'         => $this->nonce->get(),
            ],
            JSON_UNESCAPED_UNICODE,
        );

        $view->vars['label_attr'] = $labelAttr;
    }

    /**
     * @param array<string, bool|string> $attributes
     */
    private function renderUxIconHtml(string $name, array $attributes, string $fallbackHtml): string
    {
        $renderer = $this->iconRenderer;
        if ($renderer === null || !is_callable([$renderer, 'renderIcon'])) {
            return $fallbackHtml;
        }

        try {
            /** @var callable(string, array<string, bool|string>): string $render */
            $render = [$renderer, 'renderIcon'];

            return $render($name, $attributes);
        } catch (Throwable) {
            return $fallbackHtml;
        }
    }
}
