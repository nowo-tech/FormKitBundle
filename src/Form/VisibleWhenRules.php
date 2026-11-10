<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use BackedEnum;
use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Extension\VisibleWhenExtension;
use Nowo\UiKitBundle\Form\VisibleWhen;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use UnitEnum;

use function array_diff;
use function array_keys;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_scalar;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;
use function strlen;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Server twin of UiKit's `visible-when` peer for the `visible_when` field option (2.21, {@see VisibleWhenExtension}).
 *
 * A rule `{field: string, in: list<scalar|BackedEnum>, secret?: bool}` on a child says the child is shown only while
 * its sibling `field` holds one of `in`. Values compare as the browser sees them: booleans as `'1'` / `''`, backed
 * enums by value, everything else as a string; the controlling value is what the browser submits (a disabled
 * controlling field, which the browser does not submit, counts with its current value), and a multiple choice matches
 * when any submitted value does.
 *
 * {@see self::keep()} (a `PRE_SUBMIT` listener on every compound form) gives a child hidden for the submitted values
 * its current value back, so what was typed is ignored and the stored value survives (the field is still validated,
 * against that value); a hidden `secret` drops a typed value: a plain input keeps its current value, a compound secret
 * (e.g. DoctrineEncrypt's SecretType) is submitted empty with only an explicit `clear`. Rules chain like the peer (UiKit 2.43.1): a child whose controlling field is itself
 * hidden is hidden too, whatever the order of the children. A rule whose controlling field is not in the form leaves
 * the child as submitted (the peer shows it).
 *
 * Stateless — safe for FrankenPHP worker mode.
 *
 * @phpstan-type Rule array{field: string, in: list<string>, secret: bool}
 */
final class VisibleWhenRules
{
    /** UiKit `visible-when` row attributes ({@see VisibleWhen}), printed by FormKit itself. */
    public const FIELD_ATTRIBUTE = 'data-nowo-ui-visible-when-field';

    public const VALUES_ATTRIBUTE = 'data-nowo-ui-visible-when-in';

    /** Stimulus identifier of the peer; the host puts `data-controller="visible-when"` on the form. */
    public const CONTROLLER = 'visible-when';

    /** Child of a compound secret whose submitted value asks to delete the stored secret. */
    public const CLEAR_KEY = 'clear';

    private const KEYS = ['field', 'in', 'secret'];

    /**
     * Validates and normalizes a `visible_when` option (`in` values as the browser compares them).
     *
     * @throws InvalidArgumentException
     *
     * @return Rule
     */
    public static function normalize(mixed $option): array
    {
        if (!is_array($option)) {
            throw new InvalidArgumentException('visible_when is {field: string, in: list<string|int|bool|BackedEnum>, secret?: bool}.');
        }
        $unknown = array_diff(array_keys($option), self::KEYS);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf('visible_when: unknown key(s) "%s" (allowed: field, in, secret).', implode('", "', $unknown)));
        }
        $field = $option['field'] ?? null;
        if (!is_string($field)) {
            throw new InvalidArgumentException('visible_when.field must be the name of a sibling field.');
        }
        self::assertFieldName($field);
        $in = $option['in'] ?? null;
        if (!is_iterable($in)) {
            throw new InvalidArgumentException('visible_when.in must be a list of strings, integers, booleans or backed enums.');
        }
        $values = [];
        foreach ($in as $value) {
            if ($value instanceof UnitEnum && !$value instanceof BackedEnum) {
                throw new InvalidArgumentException(sprintf('visible_when.in: enum "%s" is not backed (its cases have no value the browser submits).', $value::class));
            }
            if (!is_scalar($value) && !$value instanceof BackedEnum) {
                throw new InvalidArgumentException('visible_when.in values must be strings, integers, booleans or backed enums.');
            }
            $values[] = self::visibilityValue($value);
        }
        if ($values === []) {
            throw new InvalidArgumentException('visible_when.in needs at least one value.');
        }
        $secret = $option['secret'] ?? false;
        if (!is_bool($secret)) {
            throw new InvalidArgumentException('visible_when.secret must be a boolean.');
        }

        return ['field' => $field, 'in' => array_values(array_unique($values)), 'secret' => $secret];
    }

    /**
     * String form of a value as the browser compares it: true → '1', false / null → '', backed enums by value.
     */
    public static function visibilityValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Row attributes of the UiKit `visible-when` peer (same output as UiKit's `VisibleWhen::attributes()`).
     *
     * @param string $field full `name` of the controlling input (e.g. `storage[provider]`)
     * @param list<string> $values normalized values ({@see self::normalize()})
     *
     * @return array{data-nowo-ui-visible-when-field: string, data-nowo-ui-visible-when-in: string}
     */
    public static function attributes(string $field, array $values): array
    {
        self::assertFieldName($field);

        return [
            self::FIELD_ATTRIBUTE  => $field,
            self::VALUES_ATTRIBUTE => json_encode(array_values(array_unique($values)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * `PRE_SUBMIT`: the submitted data of every child hidden for the submitted values is replaced by its current value
     * (a hidden secret by "keep", or by its explicit `clear`).
     */
    public static function keep(FormEvent $event): void
    {
        $data = $event->getData();
        if (!is_array($data)) {
            return;
        }
        $form  = $event->getForm();
        $rules = self::rules($form);
        if ($rules === []) {
            return;
        }

        $hidden = [];
        foreach ($rules as $name => $rule) {
            $values = self::controllerValues($form->get($rule['field']), $data[$rule['field']] ?? null);
            $match  = false;
            foreach ($values as $value) {
                $match = $match || in_array($value, $rule['in'], true);
            }
            if (!$match) {
                $hidden[$name] = true;
            }
        }
        // Chains (like the peer): a child controlled by a hidden child is hidden too, whatever the order.
        do {
            $changed = false;
            foreach ($rules as $name => $rule) {
                if (!isset($hidden[$name]) && isset($hidden[$rule['field']])) {
                    $hidden[$name] = true;
                    $changed       = true;
                }
            }
        } while ($changed);

        foreach ($rules as $name => $rule) {
            if (!isset($hidden[$name])) {
                continue;
            }
            $child       = $form->get($name);
            $data[$name] = $rule['secret'] ? self::keptSecret($child, $data[$name] ?? null) : self::currentSubmission($child);
        }
        $event->setData($data);
    }

    /**
     * Rules of the children of `$form` whose controlling field is a sibling (child name → rule).
     *
     * @param FormInterface<mixed> $form
     *
     * @return array<string, Rule>
     */
    public static function rules(FormInterface $form): array
    {
        $rules = [];
        foreach ($form->all() as $child) {
            /** @var Rule|null $rule normalized by VisibleWhenExtension */
            $rule = $child->getConfig()->getOption(VisibleWhenExtension::OPTION);
            $name = $child->getName();
            if (is_array($rule) && $rule['field'] !== $name && $form->has($rule['field'])) {
                $rules[$name] = $rule;
            }
        }

        return $rules;
    }

    /**
     * The child's current value in the shape a submission takes (view data; a compound child that is not a choice
     * field, child by child).
     *
     * @param FormInterface<mixed> $child
     */
    public static function currentSubmission(FormInterface $child): mixed
    {
        if ($child->getConfig()->getCompound() && $child->count() > 0 && !self::isChoice($child)) {
            $submission = [];
            foreach ($child->all() as $grandChild) {
                $submission[$grandChild->getName()] = self::currentSubmission($grandChild);
            }

            return $submission;
        }

        return $child->getViewData();
    }

    /**
     * Values the browser sends for the controlling field (one per selected value of a multiple choice; `''` for none).
     *
     * @param FormInterface<mixed> $controller
     *
     * @return list<string>
     */
    private static function controllerValues(FormInterface $controller, mixed $raw): array
    {
        if ($controller->isDisabled()) {
            // The browser does not submit a disabled input; the peer reads its value anyway.
            $raw = $controller->getViewData();
        }
        if (!is_array($raw)) {
            return [self::visibilityValue($raw)];
        }
        $values = [];
        foreach ($raw as $value) {
            if (is_scalar($value)) {
                $values[] = self::visibilityValue($value);
            }
        }

        return $values === [] ? [''] : $values;
    }

    /**
     * A hidden secret: a typed value is dropped; only an explicit `clear` of a compound secret survives.
     *
     * @param FormInterface<mixed> $child
     *
     * @return mixed the current value of a plain input; `[]` or `['clear' => …]` for a compound secret
     */
    private static function keptSecret(FormInterface $child, mixed $submitted): mixed
    {
        if (!$child->getConfig()->getCompound()) {
            // A plain secret input keeps its current value (the typed one is dropped, the stored one is not erased).
            return self::currentSubmission($child);
        }
        $clear = is_array($submitted) ? ($submitted[self::CLEAR_KEY] ?? null) : null;

        return is_string($clear) && $child->has(self::CLEAR_KEY) ? [self::CLEAR_KEY => $clear] : [];
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private static function isChoice(FormInterface $form): bool
    {
        for ($type = $form->getConfig()->getType(); $type instanceof ResolvedFormTypeInterface; $type = $type->getParent()) {
            if ($type->getInnerType() instanceof ChoiceType) {
                return true;
            }
        }

        return false;
    }

    private static function assertFieldName(string $field): void
    {
        if (trim($field) === '' || strlen($field) > 255 || preg_match('/[\x00-\x1F\x7F"]/', $field) === 1) {
            throw new InvalidArgumentException(sprintf('Invalid visible_when field "%s": the name of the controlling field.', $field));
        }
    }
}
