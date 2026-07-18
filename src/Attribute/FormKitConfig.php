<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Attribute;

use Attribute;
use ReflectionObject;

/**
 * Selects a named Form Kit config (`nowo_form_kit.configs.<name>`) for a form type.
 *
 * Prefer this on the form class instead of calling {@see \Nowo\FormKitBundle\Form\FormOptionsTrait::setFormKitConfigName()}
 * in `buildForm()`. An explicit `setFormKitConfigName()` call still wins.
 *
 * Example:
 *
 * ```php
 * use Nowo\FormKitBundle\Attribute\FormKitConfig;
 *
 * #[FormKitConfig('bootstrap')]
 * final class UserProfileType extends AbstractType
 * {
 *     use FormOptionsTrait;
 *     // ...
 * }
 * ```
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class FormKitConfig
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    /**
     * Reads the attribute from the object's class or parents. Returns null when absent.
     */
    public static function nameFrom(object $subject): ?string
    {
        $reflection = new ReflectionObject($subject);
        do {
            $attributes = $reflection->getAttributes(self::class);
            if ($attributes !== []) {
                return $attributes[0]->newInstance()->name;
            }
            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        return null;
    }
}
