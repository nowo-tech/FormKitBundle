<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sets a stateless `csrf_token_id` default on selected form types (`nowo_form_kit.stateless_csrf`).
 *
 * Symfony stores per-form CSRF tokens (token id = form name) in the session, so merely rendering an
 * anonymous public form (cookie-consent modal, newsletter, comment form…) starts a session, writes it
 * and makes the response private/uncacheable. Token ids listed in
 * `framework.csrf_protection.stateless_token_ids` are validated with the Origin/Referer header and a
 * double-submit cookie instead — no session.
 *
 * Not autoconfigured: {@see FormKitExtension} registers one service per configured form type with
 * the `form.type_extension` tag attribute `extended_type`. Symfony's form registry still checks
 * that the static {@see getExtendedTypes()} contains the type, so the configured list is
 * published once at boot ({@see NowoFormKitBundle::boot()} → {@see configureExtendedTypes()}).
 * Options passed explicitly to `createForm()` (including `csrf_token_id`) still win.
 *
 * The list comes from the compiled container (same for every request): safe in FrankenPHP
 * worker mode.
 */
final class StatelessCsrfTokenIdExtension extends AbstractTypeExtension
{
    /**
     * Configured form types (immutable for the lifetime of the kernel).
     *
     * @var list<class-string>
     */
    // @igor-ignore - immutable config published once at boot (same for every request)
    private static array $extendedTypes = []; // @phpstan-ignore frankenphp.worker.noMutableStaticProperty

    /**
     * @param list<class-string> $formTypes
     */
    public static function configureExtendedTypes(array $formTypes): void
    {
        // @igor-ignore - immutable config published once at boot (same for every request)
        self::$extendedTypes = $formTypes; // @phpstan-ignore frankenphp.worker.noMutableStaticProperty
    }

    public function __construct(
        private readonly string $tokenId,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('csrf_token_id', $this->tokenId);
    }

    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    /**
     * The configured form types ({@see configureExtendedTypes()}); each service is additionally
     * bound to one of them through its `extended_type` tag attribute.
     *
     * @return iterable<class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        return self::$extendedTypes;
    }
}
