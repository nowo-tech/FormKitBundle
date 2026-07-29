<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Constraint;

use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Validator\Constraint;

use function array_key_exists;
use function array_key_first;
use function class_exists;
use function count;
use function is_a;
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
 * Optional $messageKeyPrefix (e.g. "user_profile.email"): when set, definitions without an
 * explicit "message" option get message "{prefix}.constraints.{ConstraintShortName}".
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
    public function create(array $definitions, ?string $messageKeyPrefix = null): array
    {
        $out = [];
        foreach ($definitions as $item) {
            foreach ($this->createOne($item, $messageKeyPrefix) as $c) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * @return list<Constraint>
     */
    private function createOne(mixed $item, ?string $messageKeyPrefix): array
    {
        if ($item instanceof Constraint) {
            return [$item];
        }

        if (is_string($item)) {
            $class = $this->resolveClass($item);

            return [$this->instantiate($class, [], $messageKeyPrefix, $this->shortName($class, $item))];
        }

        if (!is_array($item)) {
            throw new InvalidArgumentException(sprintf('Constraint definition must be a string, array, or Constraint instance; got %s.', get_debug_type($item)));
        }

        if (isset($item['type'])) {
            $type  = (string) $item['type'];
            $opts  = isset($item['options']) && is_array($item['options']) ? $item['options'] : [];
            $class = $this->resolveClass($type);

            return [$this->instantiate($class, $opts, $messageKeyPrefix, $this->shortName($class, $type))];
        }

        if (count($item) === 1) {
            $name  = (string) array_key_first($item);
            $opts  = $item[$name];
            $opts  = $opts === null ? [] : (is_array($opts) ? $opts : []);
            $class = $this->resolveClass($name);

            return [$this->instantiate($class, $opts, $messageKeyPrefix, $this->shortName($class, $name))];
        }

        throw new InvalidArgumentException('Invalid constraint definition: use a one-key map (NotBlank: { ... }), type/options, or a string.');
    }

    /**
     * @param array<string, mixed> $options
     */
    /**
     * @param class-string<Constraint> $class
     * @param array<string, mixed> $options
     */
    private function instantiate(string $class, array $options, ?string $messageKeyPrefix, string $shortName): Constraint
    {
        $options = $this->applyMessageConvention($class, $options, $messageKeyPrefix, $shortName);

        /** @var Constraint $constraint */
        $constraint = new $class(...$options);

        return $constraint;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    /**
     * @param class-string<Constraint> $class
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function applyMessageConvention(string $class, array $options, ?string $messageKeyPrefix, string $shortName): array
    {
        if ($messageKeyPrefix === null || $messageKeyPrefix === '') {
            return $options;
        }

        $key = $messageKeyPrefix . '.constraints.' . $shortName;

        if (!array_key_exists('message', $options) && $this->constructorAccepts($class, 'message')) {
            $options['message'] = $key;
        }

        // Length-style constraints use minMessage / maxMessage instead of message.
        if (array_key_exists('min', $options) && !array_key_exists('minMessage', $options) && $this->constructorAccepts($class, 'minMessage')) {
            $options['minMessage'] = $key . '.min';
        }
        if (array_key_exists('max', $options) && !array_key_exists('maxMessage', $options) && $this->constructorAccepts($class, 'maxMessage')) {
            $options['maxMessage'] = $key . '.max';
        }

        return $options;
    }

    /**
     * @param class-string<Constraint> $class
     */
    private function constructorAccepts(string $class, string $paramName): bool
    {
        $ctor = (new ReflectionClass($class))->getConstructor();
        if ($ctor === null) {
            return false;
        }
        foreach ($ctor->getParameters() as $param) {
            if ($param->getName() === $paramName) {
                return true;
            }
        }

        return false;
    }

    private function shortName(string $class, string $fallback): string
    {
        if (str_contains($class, '\\')) {
            return substr($class, strrpos($class, '\\') + 1);
        }

        return $fallback;
    }

    /**
     * @return class-string<Constraint>
     */
    private function resolveClass(string $name): string
    {
        if (str_contains($name, '\\')) {
            if (!str_starts_with($name, self::CONSTRAINTS_PREFIX)) {
                throw new InvalidArgumentException(sprintf('Constraint FQCN must be under %s, got "%s".', self::CONSTRAINTS_PREFIX, $name));
            }
            if (!class_exists($name)) {
                throw new InvalidArgumentException(sprintf('Constraint class "%s" does not exist.', $name));
            }
            if (!is_a($name, Constraint::class, true)) {
                throw new InvalidArgumentException(sprintf('Constraint class "%s" must extend %s.', $name, Constraint::class));
            }

            return $name;
        }

        $fqcn = self::CONSTRAINTS_PREFIX . $name;
        if (!class_exists($fqcn)) {
            throw new InvalidArgumentException(sprintf('Unknown constraint "%s". Use a Symfony Validator constraint short name (e.g. NotBlank) or a full class under %s.', $name, self::CONSTRAINTS_PREFIX));
        }
        if (!is_a($fqcn, Constraint::class, true)) {
            throw new InvalidArgumentException(sprintf('Constraint class "%s" must extend %s.', $fqcn, Constraint::class));
        }

        return $fqcn;
    }
}
