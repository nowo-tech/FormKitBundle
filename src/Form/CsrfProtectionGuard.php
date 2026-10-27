<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use LogicException;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use function array_key_exists;
use function sprintf;

/**
 * Makes the CSRF action form types fail closed when Symfony's form CSRF extension is unavailable.
 *
 * {@see CsrfOnlyType} and {@see HiddenFieldsCsrfType} declare their own {@code csrf_protection} / {@code csrf_token_id}
 * defaults. Without {@code form.type_extension.csrf} (no {@code symfony/security-csrf}, {@code framework.csrf_protection: false}
 * or {@code framework.form.csrf_protection: false}) those options would resolve silently, no token field would be added
 * and {@code isValid()} would accept any cross-site POST. The guard throws instead.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CsrfProtectionGuard
{
    /**
     * Throws when CSRF protection is requested ({@code csrf_protection} true, the default) but the form CSRF
     * extension did not contribute its {@code csrf_token_manager} option.
     *
     * An explicit {@code csrf_protection: false} is the caller's opt-out and is left alone.
     *
     * @param array<string, mixed> $options Resolved form options
     */
    public static function assertAvailable(array $options, string $formType): void
    {
        if (array_key_exists('csrf_protection', $options) && $options['csrf_protection'] === false) {
            return;
        }

        if (($options['csrf_token_manager'] ?? null) instanceof CsrfTokenManagerInterface) {
            return;
        }

        throw new LogicException(sprintf('NowoFormKitBundle: "%s" requires Symfony form CSRF protection, but the form CSRF extension (form.type_extension.csrf) is not available. Install symfony/security-csrf and enable framework.csrf_protection and framework.form.csrf_protection; pass "csrf_protection" => false only for actions that are not state-changing.', $formType));
    }
}
