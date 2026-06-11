<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Marker interface for all FormKit model transformers.
 *
 * Extends Symfony's {@see DataTransformerInterface} to keep compatibility with
 * the Form component while allowing project-wide type-hinting.
 */
interface DataTransformer extends DataTransformerInterface
{
}

