<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Port: resolves the final options of a form field (profile defaults, convention keys, explicit options).
 *
 * The default implementation is {@see FormOptionsMerger}; alias this interface to your own service to replace it. The
 * form types, the traits and the multi-step builder of the bundle depend on this interface.
 */
interface FormOptionsMergerInterface
{
    /**
     * Resolves final options for a form field with cascading merge and convention-based keys.
     *
     * @param string|null $configName Profile name (key in profiles); when null, default_profile is used
     * @param array<string, mixed> $options Field-specific options (override convention and defaults)
     *
     * @return array<string, mixed> Merged options ready for FormBuilder::add()
     */
    public function resolve(
        string $formName,
        string $fieldName,
        string $type,
        array $options = [],
        ?string $configName = null,
    ): array;
}
