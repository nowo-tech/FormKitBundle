<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use function explode;
use function implode;
use function array_map;
use function array_values;
use function is_array;
use function is_string;
use function trim;

/**
 * Converts between a scalar CSV string (view) and an array of strings (model).
 *
 * Model:
 * - list<string>|null
 *
 * View:
 * - string like "a,b,c"
 */
final readonly class CsvModelTransformer implements DataTransformer
{
    public function __construct(
        private string $separator = ',',
        private bool $trimTokens = true,
        private bool $allowEmptyTokens = false,
    ) {
    }

    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return '';
        }

        $tokens = [];
        foreach ($value as $token) {
            if ($token === null) {
                continue;
            }

            $s = (string) $token;
            if ($this->trimTokens) {
                $s = trim($s);
            }

            if (!$this->allowEmptyTokens && $s === '') {
                continue;
            }

            $tokens[] = $s;
        }

        /** @var non-empty-string $separator */
        $separator = $this->separator !== '' ? $this->separator : ',';

        return implode($separator, $tokens);
    }

    /**
     * @return list<string>
     */
    public function reverseTransform(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            /** @var list<string> $tokens */
            $tokens = array_values(array_map(static fn (mixed $token): string => (string) $token, $value));

            return $tokens;
        }

        if (!is_string($value)) {
            return [];
        }

        $trimmed = $this->trimTokens ? trim($value) : $value;
        if ($trimmed === '') {
            return [];
        }

        /** @var non-empty-string $separator */
        $separator = $this->separator !== '' ? $this->separator : ',';

        $parts = explode($separator, $trimmed);
        $out   = [];
        foreach ($parts as $part) {
            $token = $this->trimTokens ? trim($part) : $part;

            if (!$this->allowEmptyTokens && $token === '') {
                continue;
            }

            $out[] = $token;
        }

        return $out;
    }
}
