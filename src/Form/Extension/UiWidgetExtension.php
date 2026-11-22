<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
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
use function is_string;
use function max;
use function sprintf;

/**
 * `ui_widget` option (2.24) on {@see ChoiceType} and every type extending it: `'option_cards'` draws an expanded choice
 * with the UiKit `option_cards` partial (clickable radio / checkbox cards, UiKit 2.64+), through
 * `@NowoFormKitBundle/form/field_widgets_theme.html.twig` and `form/_option_cards_field.html.twig`.
 *
 *     $builder->add('plan', ChoiceType::class, [
 *         'expanded'         => true,
 *         'choices'          => ['Basic' => 'basic', 'Pro' => 'pro'],
 *         'choice_attr'      => fn ($choice, $key, $value) => match ($value) {
 *             'basic' => ['meta' => '$9', 'description' => 'For one person', 'icon' => 'home', 'badges' => ['Popular']],
 *             default => ['meta' => '$29'],
 *         },
 *         'ui_widget'         => 'option_cards',
 *         'ui_widget_options' => ['columns' => 3, 'variant' => 'basic'],
 *     ]);
 *
 * `choice_attr` keys `description`, `meta`, `icon` and `badges` (a list of strings) feed the card and are removed from
 * the input's attributes; every other key stays on the input (`disabled` disables the card). Texts are translated in
 * the template with the choice translation domain. `ui_widget_options`: `columns` (1-4, default 2), `variant`
 * (`basic` | `icon`), `legend_sr_only` is not needed: the field label names the group (`aria-labelledby`).
 *
 * `null` (default) changes nothing. Without UiKit 2.64+ the field renders as FormKit's own cards (the `choice_cards`
 * markup). Cannot be combined with `choice_cards`. Stateless: safe for FrankenPHP worker mode.
 */
final class UiWidgetExtension extends AbstractTypeExtension
{
    public const BLOCK_PREFIX = 'nowo_option_cards';

    /** choice_attr keys that feed the card instead of the input. */
    public const CARD_KEYS = ['description', 'meta', 'icon', 'badges'];

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['ui_widget' => null, 'ui_widget_options' => []]);
        $resolver->setAllowedTypes('ui_widget', ['null', 'string']);
        $resolver->setAllowedValues('ui_widget', [null, 'option_cards']);
        $resolver->setAllowedTypes('ui_widget_options', 'array');
        $resolver->setNormalizer('ui_widget', static function (Options $options, ?string $value): ?string {
            if ($value === null) {
                return null;
            }
            if ($options['expanded'] !== true) {
                throw new InvalidOptionsException('The "ui_widget" option requires "expanded" to be true.');
            }
            if ($options['choice_cards'] !== false) {
                throw new InvalidOptionsException('The "ui_widget" option cannot be combined with "choice_cards".');
            }

            return $value;
        });
        $resolver->setNormalizer('ui_widget_options', static fn (Options $options, array $value): array => self::normalize($value));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['ui_widget'] === null) {
            return;
        }

        /** @var array{columns: int, variant: string} $widget */
        $widget                  = $options['ui_widget_options'];
        $view->vars['ui_widget'] = ['name' => $options['ui_widget']] + $widget;

        // The group is named by the row label (aria-labelledby), as for choice_cards; the same vars feed the
        // neutral fallback (the choice_cards blocks) when UiKit 2.64+ is missing.
        /** @var array<string, mixed> $labelAttr */
        $labelAttr = is_array($view->vars['label_attr'] ?? null) ? $view->vars['label_attr'] : [];
        if (!isset($labelAttr['id']) || !is_string($labelAttr['id']) || $labelAttr['id'] === '') {
            $labelAttr['id'] = $view->vars['id'] . '_label';
        }
        $view->vars['label_attr']            = $labelAttr;
        $view->vars['choice_cards_label_id'] = $view->vars['label'] === false ? null : $labelAttr['id'];
        $view->vars['choice_cards']          = ['columns' => $widget['columns']];

        foreach ($view->children as $child) {
            /** @var array<string, mixed> $attr */
            $attr = is_array($child->vars['attr'] ?? null) ? $child->vars['attr'] : [];
            $card = [];
            foreach (self::CARD_KEYS as $key) {
                if (array_key_exists($key, $attr)) {
                    $card[$key] = $this->cardValue($key, $attr[$key], (string) ($child->vars['value'] ?? ''));
                    unset($attr[$key]);
                }
            }
            $child->vars['attr']        = $attr;
            $child->vars['option_card'] = $card + ['description' => null, 'meta' => null, 'icon' => null, 'badges' => []];
            $child->vars['choice_card'] = ['description' => $child->vars['option_card']['description'], 'icon_html' => null, 'image' => null];
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
     * @return array{columns: int, variant: string}
     */
    private static function normalize(array $value): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['columns' => 2, 'variant' => 'basic']);
        $resolver->setAllowedTypes('columns', 'int');
        $resolver->setAllowedValues('columns', static fn (int $v): bool => $v >= 1 && $v <= 4);
        $resolver->setAllowedValues('variant', ['basic', 'icon']);

        try {
            /** @var array{columns: int, variant: string} $resolved */
            $resolved = $resolver->resolve($value);

            return $resolved;
        } catch (ExceptionInterface $e) {
            throw new InvalidOptionsException(sprintf('Invalid "ui_widget_options" option: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return list<string>|string|null
     */
    private function cardValue(string $key, mixed $value, string $choice): array|string|null
    {
        if (in_array($value, [null, '', []], true)) {
            return $key === 'badges' ? [] : null;
        }
        if ($key === 'badges') {
            if (!is_array($value)) {
                throw new InvalidOptionsException(sprintf('The "choice_attr" key "badges" must be a list of strings for choice "%s".', $choice));
            }
            $badges = [];
            foreach ($value as $badge) {
                if (!is_string($badge)) {
                    throw new InvalidOptionsException(sprintf('The "choice_attr" key "badges" must be a list of strings for choice "%s".', $choice));
                }
                $badges[] = $badge;
            }

            return $badges;
        }
        if (!is_string($value)) {
            throw new InvalidOptionsException(sprintf('The "choice_attr" key "%s" must be a string for choice "%s".', $key, $choice));
        }

        return $value;
    }
}
