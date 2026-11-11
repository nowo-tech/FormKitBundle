<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Throwable;

use function array_splice;
use function ceil;
use function count;
use function ctype_digit;
use function in_array;
use function is_array;
use function is_int;
use function is_iterable;
use function is_string;
use function max;
use function sprintf;

/**
 * `char_counter` option (2.22) on {@see TextType} and every type extending it (TextareaType, EmailType, SearchType…):
 * a "42 / 160" counter under the field, rendered by `@NowoFormKitBundle/form/field_widgets_theme.html.twig` and kept
 * up to date by the optional `char-counter.js` script.
 *
 *     $builder->add('title', TextType::class, ['char_counter' => true, 'constraints' => [new Length(max: 60)]]);
 *     $builder->add('bio', TextareaType::class, ['char_counter' => ['max' => 280, 'threshold' => 20]]);
 *
 * Limits, first match wins: the option's `max` / `min`; else the first `Length` constraint of the field's
 * `constraints` option; else the first `Length` constraint of the mapped property of the parent's `data_class`
 * (validator metadata, when the validator is available); else the `maxlength` / `minlength` attributes. The count
 * unit follows the `Length` constraint (`countUnit`: codepoints, graphemes or bytes) unless the option sets `unit`;
 * without a `Length` constraint it is graphemes. `threshold` (default: 10 % of `max`, at least 1) is how close to `max`
 * the remaining count starts being announced to screen readers.
 *
 * Without JavaScript the limit is printed as text under the field ("Maximum 160 characters.") and linked to the
 * input with `aria-describedby`. The option never adds `maxlength`: typing past the limit is allowed and reported, the
 * server constraint stays the rule.
 *
 * `false` (default) changes nothing. When set, the view gets `char_counter` vars and the block prefix
 * `nowo_char_counter` just before the unique block prefix.
 *
 * Stateless: the validator metadata factory caches per class on its own; safe for FrankenPHP worker mode.
 */
final class CharCounterExtension extends AbstractTypeExtension
{
    public const BLOCK_PREFIX = 'nowo_char_counter';

    private const UNITS = ['codepoints', 'graphemes', 'bytes'];

    public function __construct(
        private readonly ?MetadataFactoryInterface $metadataFactory = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('char_counter', false);
        $resolver->setAllowedTypes('char_counter', ['bool', 'array']);
        $resolver->setNormalizer('char_counter', static function (Options $options, array|bool $value): array|false {
            if ($value === false) {
                return false;
            }

            $counter = new OptionsResolver();
            $counter->setDefaults(['max' => null, 'min' => null, 'unit' => null, 'threshold' => null]);
            $counter->setAllowedTypes('max', ['null', 'int']);
            $counter->setAllowedTypes('min', ['null', 'int']);
            $counter->setAllowedTypes('threshold', ['null', 'int']);
            $counter->setAllowedValues('max', static fn (?int $v): bool => $v === null || $v > 0);
            $counter->setAllowedValues('min', static fn (?int $v): bool => $v === null || $v >= 0);
            $counter->setAllowedValues('threshold', static fn (?int $v): bool => $v === null || $v >= 0);
            $counter->setAllowedValues('unit', [null, ...self::UNITS]);

            try {
                /* @var array{max: int|null, min: int|null, unit: string|null, threshold: int|null} */
                return $counter->resolve($value === true ? [] : $value);
            } catch (ExceptionInterface $e) {
                throw new InvalidOptionsException(sprintf('Invalid "char_counter" option: %s', $e->getMessage()), 0, $e);
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array{max: int|null, min: int|null, unit: string|null, threshold: int|null}|false $counter */
        $counter = $options['char_counter'];
        if ($counter === false) {
            return;
        }

        $max    = $counter['max'];
        $min    = $counter['min'];
        $length = $this->lengthConstraint($form, $options['constraints'] ?? []);
        if ($max === null && $min === null && $length instanceof Length) {
            $max = $length->max;
            $min = $length->min !== null && $length->min > 0 ? $length->min : null;
        }
        if ($max === null && $min === null) {
            /** @var array<string, mixed> $attr */
            $attr = is_array($options['attr'] ?? null) ? $options['attr'] : [];
            $max  = $this->positiveInt($attr['maxlength'] ?? null);
            $min  = $this->positiveInt($attr['minlength'] ?? null);
        }

        $unit = $counter['unit'] ?? ($length instanceof Length ? $length->countUnit : 'graphemes');

        $view->vars['char_counter'] = [
            'max'       => $max,
            'min'       => $min,
            'unit'      => $unit,
            'threshold' => $max === null ? null : ($counter['threshold'] ?? max(1, (int) ceil($max / 10))),
        ];

        /** @var list<string> $prefixes */
        $prefixes = $view->vars['block_prefixes'] ?? [];
        if (!in_array(self::BLOCK_PREFIX, $prefixes, true)) {
            array_splice($prefixes, max(0, count($prefixes) - 1), 0, [self::BLOCK_PREFIX]);
            $view->vars['block_prefixes'] = $prefixes;
        }
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function lengthConstraint(FormInterface $form, mixed $constraints): ?Length
    {
        foreach (is_iterable($constraints) ? $constraints : [$constraints] as $constraint) {
            if ($constraint instanceof Length) {
                return $constraint;
            }
        }

        return $this->metadataLength($form);
    }

    /**
     * The first Length constraint on the mapped property of the parent's data class (all groups).
     *
     * @param FormInterface<mixed> $form
     */
    private function metadataLength(FormInterface $form): ?Length
    {
        $parent = $form->getParent();
        $path   = $form->getPropertyPath();
        if (!$this->metadataFactory instanceof MetadataFactoryInterface || !$parent instanceof FormInterface || !$path instanceof PropertyPathInterface
            || $path->getLength() !== 1 || $path->isIndex(0) || !$form->getConfig()->getMapped()) {
            return null;
        }
        $class = $parent->getConfig()->getDataClass();
        if ($class === null) {
            return null;
        }

        try {
            $metadata = $this->metadataFactory->getMetadataFor($class);
        } catch (Throwable) {
            return null;
        }
        if (!$metadata instanceof ClassMetadataInterface) {
            return null;
        }

        foreach ($metadata->getPropertyMetadata($path->getElement(0)) as $property) {
            foreach ($property->getConstraints() as $constraint) {
                if ($constraint instanceof Length) {
                    return $constraint;
                }
            }
        }

        return null;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_string($value) && $value !== '' && ctype_digit($value)) {
            return (int) $value > 0 ? (int) $value : null;
        }

        return null;
    }
}
