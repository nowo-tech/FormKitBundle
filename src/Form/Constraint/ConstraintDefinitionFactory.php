<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Constraint;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;

use function array_key_first;
use function class_exists;
use function count;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * Builds Symfony Validator Constraint instances from YAML/PHP config definitions.
 *
 * Supported shapes per entry:
 * - Constraint instance (passed through)
 * - string: short name or FQCN under Symfony\Component\Validator\Constraints\ (e.g. NotBlank, Email)
 * - { ConstraintName: options } single-key array (YAML list item)
 * - { type: NotBlank|FQCN, options?: array }
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ConstraintDefinitionFactory
{
    private const CONSTRAINTS_PREFIX = 'Symfony\\Component\\Validator\\Constraints\\';

    /**
     * @param list<mixed> $definitions
     *
     * @return list<Constraint>
     */
    public function create(array $definitions): array
    {
        $out = [];
        foreach ($definitions as $item) {
            foreach ($this->createOne($item) as $c) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * @return list<Constraint>
     */
    private function createOne(mixed $item): array
    {
        if ($item instanceof Constraint) {
            return [$item];
        }

        if (is_string($item)) {
            return [$this->instantiate($this->resolveClass($item), [])];
        }

        if (!is_array($item)) {
            throw new InvalidArgumentException(sprintf('Constraint definition must be a string, array, or Constraint instance; got %s.', get_debug_type($item)));
        }

        if (isset($item['type'])) {
            $type = (string) $item['type'];
            $opts = isset($item['options']) && is_array($item['options']) ? $item['options'] : [];

            return [$this->instantiate($this->resolveClass($type), $opts)];
        }

        if (count($item) === 1) {
            $name = (string) array_key_first($item);
            $opts = $item[$name];
            $opts = $opts === null ? [] : (is_array($opts) ? $opts : []);

            return [$this->instantiate($this->resolveClass($name), $opts)];
        }

        throw new InvalidArgumentException('Invalid constraint definition: use a one-key map (NotBlank: { ... }), type/options, or a string.');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function instantiate(string $class, array $options): Constraint
    {
        return new $class($options);
    }

    private function resolveClass(string $name): string
    {
        if (str_contains($name, '\\')) {
            if (!str_starts_with($name, self::CONSTRAINTS_PREFIX)) {
                throw new InvalidArgumentException(sprintf('Constraint FQCN must be under %s, got "%s".', self::CONSTRAINTS_PREFIX, $name));
            }
            if (!class_exists($name)) {
                throw new InvalidArgumentException(sprintf('Constraint class "%s" does not exist.', $name));
            }

            return $name;
        }

        $fqcn = self::CONSTRAINTS_PREFIX . $name;
        if (!class_exists($fqcn)) {
            throw new InvalidArgumentException(sprintf('Unknown constraint "%s". Use a Symfony Validator constraint short name (e.g. NotBlank) or a full class under %s.', $name, self::CONSTRAINTS_PREFIX));
        }

        return $fqcn;
    }
}
