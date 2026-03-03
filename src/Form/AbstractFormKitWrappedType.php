<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use ReflectionClass;
use Symfony\Component\Form\AbstractType;

/**
 * Base class for custom form types that wrap another type (e.g. UX Dropzone, Cropper).
 *
 * Extend this and implement getInnerType(); your type will delegate to the inner type
 * and use a dedicated block prefix so the bundle's FormOptionsMerger can apply
 * convention-based label/placeholder/help (and field_types.* defaults) to your type.
 *
 * Use your type with FormOptionsTrait::addWithDefaults() or buildFormFromArray(),
 * or register it in nowo_form_kit.type_map (snake_case name) and use FormKitTrait.
 *
 * Example:
 *   class DropzoneFieldType extends AbstractFormKitWrappedType {
 *     protected function getInnerType(): string { return DropzoneType::class; }
 *   }
 *   // In your form: $this->addWithDefaults($builder, 'document', DropzoneFieldType::class, []);
 */
abstract class AbstractFormKitWrappedType extends AbstractType
{
    /**
     * FQCN of the form type this type wraps (e.g. DropzoneType::class).
     *
     * @return class-string
     */
    abstract protected function getInnerType(): string;

    public function getParent(): string
    {
        return $this->getInnerType();
    }

    public function getBlockPrefix(): string
    {
        $shortName   = (new ReflectionClass($this))->getShortName();
        $withoutType = preg_replace('/Type$/', '', $shortName);

        return $this->camelCaseToSnakeCase($withoutType);
    }

    private function camelCaseToSnakeCase(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_\\0', lcfirst($name)));
    }
}
