<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Twig;

use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function htmlspecialchars;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Twig functions that print the help-modal nonce ({@see HelpModalNonce}) for `help-modal.js`.
 *
 * Put `{{ nowo_form_kit_help_modal_meta() }}` inside `<head>` on every page that renders forms with `help_modal`.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class HelpModalTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly HelpModalNonce $nonce,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nowo_form_kit_help_modal_nonce', $this->nonce(...)),
            new TwigFunction('nowo_form_kit_help_modal_meta', $this->meta(...), ['is_safe' => ['html']]),
        ];
    }

    /** Nonce of the current request. */
    public function nonce(): string
    {
        return $this->nonce->get();
    }

    /** `<meta name="nowo-form-kit-help-modal-nonce" content="…">` for `<head>`. */
    public function meta(): string
    {
        return '<meta name="' . HelpModalNonce::META_NAME . '" content="'
            . htmlspecialchars($this->nonce->get(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}
