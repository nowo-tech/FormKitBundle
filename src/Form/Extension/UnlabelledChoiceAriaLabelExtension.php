<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_array;
use function is_string;

/**
 * Gives unlabelled selects an accessible name (`nowo_form_kit.accessibility.unlabelled_choice_aria_label`).
 *
 * Compact filter bars render selects with `label: false` (e.g. the built-in `filter` profile), which leaves
 * them without an accessible name: screen readers announce "combo box, Any level". When a non-expanded
 * {@see ChoiceType} field (and every type extending it, such as EntityType, CountryType, LocaleType…) has
 * no label, no `aria-label` and no `aria-labelledby`, this extension sets `aria-label` from its translated
 * help text, else from its translated empty-option placeholder. `translation_domain: false` uses the raw
 * text. Fields that already carry an accessible name are never touched, so the extension is idempotent.
 *
 * Registered by {@see FormKitExtension} only when the option is enabled (default). Stateless: safe in
 * FrankenPHP worker mode.
 */
final class UnlabelledChoiceAriaLabelExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly ?TranslatorInterface $translator = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (($view->vars['label'] ?? null) !== false || ($view->vars['expanded'] ?? false)) {
            return;
        }

        $attr = is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [];
        if (isset($attr['aria-label']) || isset($attr['aria-labelledby'])) {
            return;
        }

        $domain = $view->vars['translation_domain'] ?? null;
        foreach ([$view->vars['help'] ?? null, $view->vars['placeholder'] ?? null] as $text) {
            if (is_string($text) && $text !== '') {
                $attr['aria-label'] = $this->translate($text, $domain);
                $view->vars['attr'] = $attr;

                return;
            }
        }
    }

    private function translate(string $text, mixed $domain): string
    {
        if ($domain === false || !$this->translator instanceof TranslatorInterface) {
            return $text;
        }

        return $this->translator->trans($text, [], is_string($domain) ? $domain : null);
    }
}
