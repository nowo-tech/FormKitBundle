<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\DataTransformer;

use InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function is_float;
use function is_int;
use function is_string;
use function strlen;

use const STR_PAD_LEFT;

/**
 * Transforms between a model "integer cents" scalar and a decimal string for a text input.
 *
 * Model:
 * - int|null (e.g. 1234 => "12.34" when scale=2)
 *
 * View:
 * - string like "12.34" (comma accepted as decimal separator)
 */
final class MoneyModelTransformer implements DataTransformer
{
    public function __construct(
        private readonly int $scale = 2,
    ) {
        if ($this->scale < 0) {
            throw new InvalidArgumentException('scale must be >= 0');
        }
    }

    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $cents = $this->coerceModelToCents($value);
        if ($this->scale === 0) {
            return (string) $cents;
        }

        $divisor  = $this->divisor();
        $negative = $cents < 0;
        $abs      = abs($cents);

        $intPart  = intdiv($abs, $divisor);
        $fracPart = $abs % $divisor;

        $fraction = str_pad((string) $fracPart, $this->scale, '0', STR_PAD_LEFT);
        $out      = $intPart . '.' . $fraction;

        return $negative ? '-' . $out : $out;
    }

    public function reverseTransform(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Invalid money input type.');
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        // Allow comma as decimal separator.
        $normalized = str_replace(' ', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new TransformationFailedException('Invalid money format.');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;

        $parts      = explode('.', $unsigned, 2);
        $whole      = $parts[0] === '' ? 0 : (int) $parts[0];
        $fracDigits = $parts[1] ?? '';

        $divisor = $this->divisor();

        if ($this->scale === 0) {
            $result = $whole;

            return $negative ? -$result : $result;
        }

        $roundUp = false;
        if (strlen($fracDigits) > $this->scale) {
            $nextDigit  = $fracDigits[$this->scale] ?? '0';
            $roundUp    = ((int) $nextDigit) >= 5;
            $fracDigits = substr($fracDigits, 0, $this->scale);
        }

        $fracDigits = str_pad($fracDigits, $this->scale, '0');
        $frac       = (int) $fracDigits;

        if ($roundUp) {
            ++$frac;
            if ($frac >= $divisor) {
                $frac %= $divisor;
                ++$whole;
            }
        }

        $cents = $whole * $divisor + $frac;

        return $negative ? -$cents : $cents;
    }

    private function divisor(): int
    {
        $d = 1;
        for ($i = 0; $i < $this->scale; ++$i) {
            $d *= 10;
        }

        return $d;
    }

    private function coerceModelToCents(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            $divisor = $this->divisor();

            return (int) round($value * $divisor);
        }

        if (is_string($value)) {
            $s = trim($value);
            if ($s === '') {
                return 0;
            }

            // If model is already integer cents.
            if (preg_match('/^-?\d+$/', $s) === 1) {
                return (int) $s;
            }

            // If model is a decimal string, interpret it as the human amount.
            $s = str_replace(',', '.', $s);
            if (!preg_match('/^-?\d+(\.\d+)?$/', $s)) {
                throw new TransformationFailedException('Invalid money model value.');
            }

            $tmp = $this->reverseTransform($s);
            if ($tmp === null) {
                return 0;
            }

            return $tmp;
        }

        throw new TransformationFailedException('Invalid money model type.');
    }
}
