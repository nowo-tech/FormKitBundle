<?php

declare(strict_types=1);

namespace App\Form\Type;

use Nowo\FormKitBundle\Form\AbstractFormKitWrappedType;
use Symfony\UX\Dropzone\Form\DropzoneType;

/**
 * Custom "layer" type: wraps UX DropzoneType and integrates with Form Kit conventions.
 * Use with FormOptionsTrait::addWithDefaults() or buildFormFromArray() so label/placeholder/help
 * come from dropzone_demo.document.* (or your form prefix + field name).
 */
final class DropzoneFieldType extends AbstractFormKitWrappedType
{
    protected function getInnerType(): string
    {
        return DropzoneType::class;
    }
}
