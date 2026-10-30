<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\HelpModal;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps responses whose help-modal nonce came from the request header out of shared caches (REQ-SEC-004, finding L3):
 * adds `Vary: X-Nowo-Form-Kit-Help-Modal-Nonce` and `Cache-Control: private`.
 *
 * Stateless (reads the main request attributes only): safe in FrankenPHP worker mode.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class HelpModalNonceResponseListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // Low priority: run after controllers / other listeners that set cache headers.
        return [KernelEvents::RESPONSE => ['onKernelResponse', -255]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->attributes->get(HelpModalNonce::REUSED_ATTRIBUTE) !== true) {
            return;
        }

        $response = $event->getResponse();
        // @igor-ignore - per-request Response object (not shared state)
        $response->setVary(HelpModalNonce::HEADER, false);
        // @igor-ignore - per-request Response object (not shared state)
        $response->setPrivate();
    }
}
