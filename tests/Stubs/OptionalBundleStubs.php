<?php

declare(strict_types=1);

namespace Symfony\UX\Dropzone\Form;

use Symfony\Component\Form\AbstractType;

if (!class_exists(DropzoneType::class)) {
    /** @extends AbstractType<mixed> */
    final class DropzoneType extends AbstractType
    {
    }
}

namespace Symfony\UX\Cropperjs\Form;

use Symfony\Component\Form\AbstractType;

if (!class_exists(CropperType::class)) {
    /** @extends AbstractType<mixed> */
    final class CropperType extends AbstractType
    {
    }
}

namespace Nowo\SelectAllChoiceBundle;

if (!class_exists(NowoSelectAllChoiceBundle::class)) {
    final class NowoSelectAllChoiceBundle
    {
    }
}

namespace FOS\CKEditorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

if (!class_exists(CKEditorType::class)) {
    /** @extends AbstractType<mixed> */
    final class CKEditorType extends AbstractType
    {
    }
}
