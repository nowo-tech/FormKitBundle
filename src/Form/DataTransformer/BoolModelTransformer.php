<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

/**
 * Converts model values (0/1, 1/0, "1"/"0", true/false) into a boolean view value
 * for CheckboxType, and back to the model "on/off" scalar.
 */
final class BoolModelTransformer implements DataTransformer
{
    public function __construct(
        private readonly int $onValue = 1,
        private readonly int $offValue = 0,
    ) {
    }

    public function transform(mixed $value): bool
    {
        if ($value === true) {
            return true;
        }

        if ($value === false || $value === null) {
            return false;
        }

        if (is_int($value)) {
            return $value === $this->onValue;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return false;
            }

            if ($trimmed === 'true') {
                return true;
            }

            if ($trimmed === 'false') {
                return false;
            }

            return (int) $trimmed === $this->onValue;
        }

        return false;
    }

    public function reverseTransform(mixed $value): int
    {
        if ($value === true) {
            return $this->onValue;
        }

        if ($value === false || $value === null) {
            return $this->offValue;
        }

        if (is_int($value)) {
            return $value === $this->onValue ? $this->onValue : $this->offValue;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return $this->offValue;
            }

            if ($trimmed === 'true') {
                return $this->onValue;
            }

            if ($trimmed === 'false') {
                return $this->offValue;
            }

            return (int) $trimmed === $this->onValue ? $this->onValue : $this->offValue;
        }

        return $this->offValue;
    }
}

