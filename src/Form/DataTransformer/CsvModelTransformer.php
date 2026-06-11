<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use function explode;
use function implode;
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
final class CsvModelTransformer implements DataTransformer
{
    public function __construct(
        private readonly string $separator = ',',
        private readonly bool $trimTokens = true,
        private readonly bool $allowEmptyTokens = false,
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

        return implode($this->separator, $tokens);
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
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $trimmed = $this->trimTokens ? trim($value) : $value;
        if ($trimmed === '') {
            return [];
        }

        $parts = explode($this->separator, $trimmed);
        $out   = [];
        foreach ($parts as $part) {
            $token = $this->trimTokens ? trim($part) : $part;

            if (!$this->allowEmptyTokens && $token === '') {
                continue;
            }

            $out[] = (string) $token;
        }

        return $out;
    }
}
