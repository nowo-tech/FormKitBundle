<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\HelpModal;

/**
 * Port: the per-request nonce that binds help-modal payloads to the page.
 *
 * The default implementation is {@see HelpModalNonce} (its header and attribute constants stay on the class); alias
 * this interface to your own service to replace it.
 */
interface HelpModalNonceInterface
{
    /**
     * Nonce of the current main request (generated once per request).
     */
    public function get(): string;
}
