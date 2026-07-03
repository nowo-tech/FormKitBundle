<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use function is_array;
use function is_int;
use function is_string;

/**
 * Transforms between a scalar "on/off" model value and the ChoiceType view array.
 *
 * Model:
 * - 1 or true => [1]
 * - null/0/false => []
 *
 * View:
 * - contains switchValue (int or string) => switchValue (default 1)
 * - otherwise => 0
 *
 * Intended for ChoiceType configured as expanded + multiple, with a single choice
 * like ['some.label.key' => 1].
 */
final readonly class SwitchModelTransformer implements DataTransformer
{
    public function __construct(
        private int $switchValue = 1,
        private int $offValue = 0,
    ) {
    }

    /**
     * @param mixed $value Model value (usually int/bool|null)
     *
     * @return list<int>
     */
    public function transform(mixed $value): array
    {
        if ($value === true) {
            return [$this->switchValue];
        }

        if (is_int($value) && $value === $this->switchValue) {
            return [$this->switchValue];
        }

        if (is_string($value) && $value !== '' && (int) $value === $this->switchValue) {
            return [$this->switchValue];
        }

        return [];
    }

    /**
     * @param mixed $value View array (usually array of ints/strings)
     *
     * @return int On/off value for the model
     */
    public function reverseTransform(mixed $value): int
    {
        if (!is_array($value)) {
            return $this->offValue;
        }

        // ChoiceType may represent selected values as strings (e.g. "1") depending on configuration.
        foreach ($value as $v) {
            if ($v === $this->switchValue || $v === (string) $this->switchValue) {
                return $this->switchValue;
            }
        }

        return $this->offValue;
    }
}
