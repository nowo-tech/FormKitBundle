<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\DataTransformer\DataTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_string;

/**
 * DDD host model transformer: string <-> {@see EmailAddress}.
 */
final class EmailAddressTransformer implements DataTransformer
{
    public function transform(mixed $value): string
    {
        return $value instanceof EmailAddress ? $value->value : '';
    }

    public function reverseTransform(mixed $value): ?EmailAddress
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return EmailAddress::fromString($value);
        } catch (InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }
}
