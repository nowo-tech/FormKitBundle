<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_splice;
use function count;
use function in_array;

/**
 * Adds the `locale_tabs` option (false|array, default false) to every form type.
 *
 * When set on a compound field whose children are keyed by locale, the view gets
 * `locale_tabs` vars and the block prefix `nowo_locale_tabs` (just before the unique
 * block prefix), so `@NowoFormKitBundle/form/locale_tabs_theme.html.twig` renders the
 * row as one ARIA tab per locale. Array keys are forwarded to `_locale_tabs.html.twig`
 * (locales, default_locale, title, help, label, tabs_label, locale_labels, …).
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class LocaleTabsExtension extends AbstractTypeExtension
{
    public const BLOCK_PREFIX = 'nowo_locale_tabs';

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('locale_tabs', false);
        $resolver->setAllowedTypes('locale_tabs', ['bool', 'array']);
        $resolver->setNormalizer('locale_tabs', static fn ($options, array|bool $value): array|false => $value === true ? [] : $value);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<string, mixed>|false $localeTabs */
        $localeTabs = $options['locale_tabs'];
        if ($localeTabs === false) {
            return;
        }

        $view->vars['locale_tabs'] = $localeTabs;

        /** @var list<string> $prefixes */
        $prefixes = $view->vars['block_prefixes'] ?? [];
        if (in_array(self::BLOCK_PREFIX, $prefixes, true)) {
            return;
        }

        // Insert before the unique block prefix (last entry) so the theme block wins over type blocks.
        $position = max(0, count($prefixes) - 1);
        array_splice($prefixes, $position, 0, [self::BLOCK_PREFIX]);
        $view->vars['block_prefixes'] = $prefixes;
    }
}
