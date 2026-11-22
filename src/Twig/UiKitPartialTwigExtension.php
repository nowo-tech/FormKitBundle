<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Twig;

use Throwable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function is_callable;
use function is_string;

/**
 * `nowo_form_kit_ui_partial(name)` (2.24): the template of a UiKit theme partial, or null when UiKit is not installed
 * or is too old to have it. Today only `option_cards` (UiKit 2.64+) is resolved; the form theme falls back to the
 * neutral FormKit markup on null.
 *
 * Stateless: nothing is memoised, so it is safe for FrankenPHP worker mode.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class UiKitPartialTwigExtension extends AbstractExtension
{
    /** Partial name => the template file UiKit ships for it (checked, so an older UiKit gives null). */
    private const TEMPLATES = ['option_cards' => '@NowoUiKitBundle/partials/_option_cards.html.twig'];

    public function getFunctions(): array
    {
        return [new TwigFunction('nowo_form_kit_ui_partial', $this->partial(...), ['needs_environment' => true])];
    }

    public function partial(Environment $env, string $name): ?string
    {
        if ($name !== 'option_cards') {
            return null;
        }
        $resolve = $env->getFunction('nowo_ui_partial')?->getCallable();
        if (!is_callable($resolve) || !$env->getLoader()->exists(self::TEMPLATES[$name])) {
            return null;
        }
        try {
            $template = $resolve($name);
        } catch (Throwable) {
            return null;
        }

        return is_string($template) && $template !== '' ? $template : null;
    }
}
