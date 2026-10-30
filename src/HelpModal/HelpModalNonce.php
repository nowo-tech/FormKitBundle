<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\HelpModal;

use Nowo\FormKitBundle\Form\Extension\HelpModalExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function base64_encode;
use function is_string;
use function preg_match;
use function random_bytes;
use function rtrim;
use function str_starts_with;
use function strtr;

/**
 * Per-request nonce that binds help-modal payloads to the server render (REQ-SEC-004, finding L1).
 *
 * {@see HelpModalExtension} puts it in every `data-nowo-help-modal` payload and
 * `{{ nowo_form_kit_help_modal_meta() }}` prints it in `<head>`; `help-modal.js` only honours payloads whose nonce
 * equals the head meta, so a label forged through stored user HTML (which cannot know the value) is ignored.
 *
 * Stateless: the value lives in the main request attributes (never in this service), so it is safe in FrankenPHP
 * worker mode. A same-origin Turbo Frame request (`Turbo-Frame` header) may send the page nonce back in
 * {@see HEADER}; it is reused when well-formed so the fragment's labels stay bound to the page that loaded them.
 * Top-level requests never reuse it (REQ-SEC-004, finding L3: a shared cache could otherwise be primed with a chosen
 * nonce), and a response that reused it is marked `Cache-Control: private` + `Vary: X-Nowo-Form-Kit-Help-Modal-Nonce`
 * by {@see HelpModalNonceResponseListener}.
 */
final readonly class HelpModalNonce
{
    /** Main request attribute holding the nonce of the current request. */
    public const REQUEST_ATTRIBUTE = '_nowo_form_kit_help_modal_nonce';

    /** Header a fragment request uses to reuse the nonce of the page that issued it. */
    public const HEADER = 'X-Nowo-Form-Kit-Help-Modal-Nonce';

    /** Main request attribute set to true when the nonce was taken from {@see HEADER} (response must not be shared-cached). */
    public const REUSED_ATTRIBUTE = '_nowo_form_kit_help_modal_nonce_reused';

    /** `<meta name>` read by help-modal.js (in `document.head` only). */
    public const META_NAME = 'nowo-form-kit-help-modal-nonce';

    /** Accepted shape of a nonce sent back by the browser (base64url, 128+ bits). */
    private const PATTERN = '/^[A-Za-z0-9_-]{22,128}$/';

    public function __construct(
        private ?RequestStack $requestStack = null,
    ) {
    }

    /**
     * Nonce of the current main request (generated once per request). Without a request (CLI) a fresh random value
     * is returned on every call.
     */
    public function get(): string
    {
        $request = $this->requestStack?->getMainRequest();
        if (!$request instanceof Request) {
            return $this->generate();
        }

        $nonce = $request->attributes->get(self::REQUEST_ATTRIBUTE);
        if (is_string($nonce)) {
            return $nonce;
        }

        $header = $request->headers->get(self::HEADER);
        $reuse  = is_string($header) && preg_match(self::PATTERN, $header) === 1 && $this->isSameOriginFragment($request);
        $nonce  = $reuse ? $header : $this->generate();
        // @igor-ignore - per-request Request object (not shared state): the nonce lives and dies with this request
        $request->attributes->set(self::REQUEST_ATTRIBUTE, $nonce);
        if ($reuse) {
            // @igor-ignore - per-request Request object (not shared state)
            $request->attributes->set(self::REUSED_ATTRIBUTE, true);
        }

        return $nonce;
    }

    /**
     * Fragment request (Turbo Frame) that the browser reports as same-origin. `Sec-Fetch-Site`, when sent, must be
     * `same-origin`; otherwise `Origin` (or `Referer`), when sent, must match the request scheme and host.
     */
    private function isSameOriginFragment(Request $request): bool
    {
        if (!$request->headers->has('Turbo-Frame')) {
            return false;
        }

        $site = $request->headers->get('Sec-Fetch-Site');
        if (is_string($site)) {
            return $site === 'same-origin';
        }

        $origin = $request->headers->get('Origin') ?? $request->headers->get('Referer');
        if (!is_string($origin)) {
            return true;
        }

        return str_starts_with($origin . '/', $request->getSchemeAndHttpHost() . '/');
    }

    /** 144 random bits, base64url without padding (24 characters). */
    private function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }
}
