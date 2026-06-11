<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_string;

use const JSON_ERROR_NONE;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

/**
 * Transforms JSON-compatible model values (array/object/null) to a pretty JSON string
 * for textarea widgets, and back to associative arrays on submit.
 */
final class JsonModelTransformer implements DataTransformer
{
    public function __construct(
        private readonly bool $prettyPrint = true,
        private readonly bool $unescapedUnicode = true,
    ) {
    }

    public function transform(mixed $jsonArray): mixed
    {
        $flags = 0;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }
        if ($this->unescapedUnicode) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }

        $encoded = json_encode($jsonArray, $flags);
        if ($encoded === false) {
            throw new TransformationFailedException('Could not encode JSON value.');
        }

        return $encoded;
    }

    public function reverseTransform(mixed $stringArray): mixed
    {
        if ($stringArray === null || $stringArray === '') {
            return [];
        }

        if (!is_string($stringArray)) {
            throw new TransformationFailedException('Invalid JSON input type.');
        }

        $decoded = json_decode($stringArray, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TransformationFailedException('Could not decode JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
