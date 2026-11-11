<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_splice;
use function count;
use function in_array;
use function max;
use function sprintf;

/**
 * `switch` option (2.22) on {@see CheckboxType}: the checkbox row is drawn as an on/off switch by
 * `@NowoFormKitBundle/form/field_widgets_theme.html.twig`, the same markup on every CSS framework.
 *
 *     $builder->add('notifications', CheckboxType::class, ['switch' => true]);
 *     $builder->add('dark_mode', CheckboxType::class, ['switch' => ['size' => 'lg', 'icons' => true]]);
 *
 * It stays a real `<input type="checkbox">` (with `role="switch"`), submitted and validated as before; the switch is
 * the checkbox's `<label>`, so its text and state are read by screen readers and the thumb position (plus the
 * optional on/off icons) shows the state without relying on colour.
 *
 * `false` (default) changes nothing. When set, the view gets `switch` vars (`size`: `sm` | `md` | `lg`, `icons`) and
 * the block prefix `nowo_switch` just before the unique block prefix, so `nowo_switch_row` / `nowo_switch_widget`
 * replace the theme's checkbox row. Radios (which extend CheckboxType) reject the option.
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class SwitchExtension extends AbstractTypeExtension
{
    public const BLOCK_PREFIX = 'nowo_switch';

    public static function getExtendedTypes(): iterable
    {
        return [CheckboxType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('switch', false);
        $resolver->setAllowedTypes('switch', ['bool', 'array']);
        $resolver->setNormalizer('switch', static function (Options $options, array|bool $value): array|false {
            if ($value === false) {
                return false;
            }

            $switch = new OptionsResolver();
            $switch->setDefaults(['size' => 'md', 'icons' => false]);
            $switch->setAllowedValues('size', ['sm', 'md', 'lg']);
            $switch->setAllowedTypes('icons', 'bool');

            try {
                /* @var array{size: string, icons: bool} */
                return $switch->resolve($value === true ? [] : $value);
            } catch (ExceptionInterface $e) {
                throw new InvalidOptionsException(sprintf('Invalid "switch" option: %s', $e->getMessage()), 0, $e);
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array{size: string, icons: bool}|false $switch */
        $switch = $options['switch'];
        if ($switch === false) {
            return;
        }

        /** @var list<string> $prefixes */
        $prefixes = $view->vars['block_prefixes'] ?? [];
        if (in_array('radio', $prefixes, true)) {
            throw new InvalidOptionsException('The "switch" option is only available on checkboxes, not on radios.');
        }

        $view->vars['switch'] = $switch;
        if (!in_array(self::BLOCK_PREFIX, $prefixes, true)) {
            array_splice($prefixes, max(0, count($prefixes) - 1), 0, [self::BLOCK_PREFIX]);
            $view->vars['block_prefixes'] = $prefixes;
        }
    }
}
