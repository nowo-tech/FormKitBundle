<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Closure;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_key_exists;
use function array_splice;
use function count;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function max;
use function sprintf;

/**
 * `choice_cards` option (2.22) on {@see ChoiceType} and every type extending it (EntityType, EnumType…): an expanded
 * choice is drawn as selectable cards (title, optional description, icon or image) by
 * `@NowoFormKitBundle/form/field_widgets_theme.html.twig`.
 *
 *     $builder->add('plan', ChoiceType::class, [
 *         'expanded'     => true,
 *         'choices'      => ['Basic' => 'basic', 'Pro' => 'pro'],
 *         'choice_cards' => [
 *             'description' => ['basic' => 'For one person', 'pro' => 'For teams'],      // or fn ($choice, $key, $value)
 *             'image'       => fn ($choice, $key, $value) => ['src' => '/img/' . $value . '.svg', 'alt' => ''],
 *             'columns'     => 2,
 *         ],
 *     ]);
 *
 * `false` (default) changes nothing. When set, the view gets the block prefix `nowo_choice_cards` (just before the
 * unique block prefix, so `nowo_choice_cards_widget` replaces the theme's `choice_widget_expanded`), the group gets an
 * accessible name from its label (`label_attr.id`, defaulting to `<id>_label`), and each child gets `choice_card`
 * vars resolved from the option: `description`, `icon_html` (trusted HTML) and `image` (`{src, alt}`; `alt` is
 * required, an empty string marks a decorative image). Resolvers are an array keyed by choice value or a callable
 * with the `choice_attr` signature `($choice, $key, $value)`. Texts are translated in the template with the
 * choice translation domain and parameters.
 *
 * Stateless: resolvers are form options; safe for FrankenPHP worker mode.
 */
final class ChoiceCardsExtension extends AbstractTypeExtension
{
    public const BLOCK_PREFIX = 'nowo_choice_cards';

    private const RESOLVERS = ['description', 'icon_html', 'image'];

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choice_cards', false);
        $resolver->setAllowedTypes('choice_cards', ['bool', 'array']);
        $resolver->setNormalizer('choice_cards', static function (Options $options, array|bool $value): array|false {
            if ($value === false) {
                return false;
            }
            if ($options['expanded'] !== true) {
                throw new InvalidOptionsException('The "choice_cards" option requires "expanded" to be true.');
            }

            return self::normalize($value === true ? [] : $value);
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array{description: array<array-key, mixed>|Closure|null, icon_html: array<array-key, mixed>|Closure|null, image: array<array-key, mixed>|Closure|null, columns: int|null}|false $cards */
        $cards = $options['choice_cards'];
        if ($cards === false) {
            return;
        }

        $view->vars['choice_cards'] = ['columns' => $cards['columns']];

        /** @var array<string, mixed> $labelAttr */
        $labelAttr = is_array($view->vars['label_attr'] ?? null) ? $view->vars['label_attr'] : [];
        if (!isset($labelAttr['id']) || !is_string($labelAttr['id']) || $labelAttr['id'] === '') {
            $labelAttr['id'] = $view->vars['id'] . '_label';
        }
        $view->vars['label_attr']            = $labelAttr;
        $view->vars['choice_cards_label_id'] = $view->vars['label'] === false ? null : $labelAttr['id'];

        $choiceList = $form->getConfig()->getAttribute('choice_list');
        $keys       = $choiceList instanceof ChoiceListInterface ? $choiceList->getOriginalKeys() : [];
        foreach ($view->children as $name => $child) {
            $value  = (string) ($child->vars['value'] ?? '');
            $choice = null;
            if ($choiceList instanceof ChoiceListInterface && $value !== '') {
                $choice = $choiceList->getChoicesForValues([$value])[0] ?? null;
            }
            $key = $keys[$value] ?? $name;

            $child->vars['choice_card'] = [
                'description' => $this->text($cards['description'], $choice, $key, $value, 'description'),
                'icon_html'   => $this->text($cards['icon_html'], $choice, $key, $value, 'icon_html'),
                'image'       => $this->image($cards['image'], $choice, $key, $value),
            ];
        }

        /** @var list<string> $prefixes */
        $prefixes = $view->vars['block_prefixes'] ?? [];
        if (!in_array(self::BLOCK_PREFIX, $prefixes, true)) {
            array_splice($prefixes, max(0, count($prefixes) - 1), 0, [self::BLOCK_PREFIX]);
            $view->vars['block_prefixes'] = $prefixes;
        }
    }

    /**
     * @param array<mixed> $value
     *
     * @return array{description: array<array-key, mixed>|Closure|null, icon_html: array<array-key, mixed>|Closure|null, image: array<array-key, mixed>|Closure|null, columns: int|null}
     */
    private static function normalize(array $value): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['description' => null, 'icon_html' => null, 'image' => null, 'columns' => null]);
        foreach (self::RESOLVERS as $key) {
            $resolver->setAllowedTypes($key, ['null', 'array', 'callable']);
            $resolver->setNormalizer($key, static fn (Options $o, mixed $v): array|Closure|null => is_callable($v) ? Closure::fromCallable($v) : $v);
        }
        $resolver->setAllowedTypes('columns', ['null', 'int']);
        $resolver->setAllowedValues('columns', static fn (?int $v): bool => $v === null || ($v >= 1 && $v <= 4));

        try {
            /** @var array{description: array<array-key, mixed>|Closure|null, icon_html: array<array-key, mixed>|Closure|null, image: array<array-key, mixed>|Closure|null, columns: int|null} $resolved */
            $resolved = $resolver->resolve($value);

            return $resolved;
        } catch (ExceptionInterface $e) {
            throw new InvalidOptionsException(sprintf('Invalid "choice_cards" option: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @param array<array-key, mixed>|Closure|null $resolver
     */
    private function resolve(array|Closure|null $resolver, mixed $choice, int|string $key, string $value): mixed
    {
        if ($resolver instanceof Closure) {
            return $resolver($choice, $key, $value);
        }

        return is_array($resolver) && array_key_exists($value, $resolver) ? $resolver[$value] : null;
    }

    /**
     * @param array<array-key, mixed>|Closure|null $resolver
     */
    private function text(array|Closure|null $resolver, mixed $choice, int|string $key, string $value, string $name): ?string
    {
        $text = $this->resolve($resolver, $choice, $key, $value);
        if ($text === null || $text === '') {
            return null;
        }
        if (!is_string($text)) {
            throw new InvalidOptionsException(sprintf('The "choice_cards.%s" resolver must return a string or null for choice "%s".', $name, $value));
        }

        return $text;
    }

    /**
     * @param array<array-key, mixed>|Closure|null $resolver
     *
     * @return array{src: string, alt: string}|null
     */
    private function image(array|Closure|null $resolver, mixed $choice, int|string $key, string $value): ?array
    {
        $image = $this->resolve($resolver, $choice, $key, $value);
        if ($image === null) {
            return null;
        }
        if (!is_array($image) || !is_string($image['src'] ?? null) || $image['src'] === '' || !is_string($image['alt'] ?? null)) {
            throw new InvalidOptionsException(sprintf('The "choice_cards.image" resolver must return null or ["src" => non-empty string, "alt" => string] for choice "%s" (use "alt" => "" for a decorative image).', $value));
        }

        return ['src' => $image['src'], 'alt' => $image['alt']];
    }
}
