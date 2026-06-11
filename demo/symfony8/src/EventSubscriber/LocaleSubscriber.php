<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function in_array;
use function is_string;

/**
 * Keeps session locale in sync with the URL segment /{_locale}/ (and optional legacy ?_locale=).
 */
final class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<int, string> $enabledLocales
     */
    public function __construct(
        private readonly array $enabledLocales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 16]]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $fromRoute = $request->attributes->get('_locale');
        if (is_string($fromRoute) && $fromRoute !== '' && in_array($fromRoute, $this->enabledLocales, true)) {
            $request->setLocale($fromRoute);
            if ($request->hasSession()) {
                $request->getSession()->set('_locale', $fromRoute);
            }

            return;
        }

        $locale = $request->query->get('_locale');
        if (is_string($locale) && $locale !== '' && in_array($locale, $this->enabledLocales, true)) {
            $request->setLocale($locale);
            if ($request->hasSession()) {
                $request->getSession()->set('_locale', $locale);
            }

            return;
        }

        if ($request->hasSession()) {
            $sessionLocale = $request->getSession()->get('_locale');
            if (is_string($sessionLocale) && $sessionLocale !== '' && in_array($sessionLocale, $this->enabledLocales, true)) {
                $request->setLocale($sessionLocale);
            }
        }
    }
}
