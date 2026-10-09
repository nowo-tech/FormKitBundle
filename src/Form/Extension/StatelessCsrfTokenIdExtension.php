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
 * Not autoconfigured: {@see FormKitExtension} registers one
 * service per configured form type with the `form.type_extension` tag attribute `extended_type`
 * (that is why {@see getExtendedTypes()} is empty). Options passed explicitly to `createForm()`
 * (including `csrf_token_id`) still win over this default.
 *
 * Stateless (readonly token id) — safe for FrankenPHP worker mode.
 */
final class StatelessCsrfTokenIdExtension extends AbstractTypeExtension
{
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
     * Extended types are provided per service via the `extended_type` tag attribute.
     *
     * @return iterable<class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [];
    }
}
