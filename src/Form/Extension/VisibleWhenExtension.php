<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use InvalidArgumentException;
use LogicException;
use Nowo\FormKitBundle\Form\VisibleWhenRules;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_merge;
use function is_array;
use function is_string;
use function sprintf;

/**
 * The `visible_when` option on every form field (2.21): `{field: string, in: list<scalar|BackedEnum>, secret?: bool}`
 * shows the field only while its sibling `field` holds one of `in` (provider-dependent fields: SMTP host for `smtp`,
 * API key for `ses`…). Default `null`: nothing printed, nothing changed.
 *
 * - The row gets UiKit's `visible-when` attributes in `row_attr` (merged with the field's own `row_attr`; the
 *   controlling input is named by its full `name`), so the peer hides it without disabling or clearing it. The host
 *   puts `data-controller="visible-when"` on the form and loads the peer (UiKit ^2.23; chains with ^2.43.1).
 * - On submit ({@see VisibleWhenRules::keep()}, one `PRE_SUBMIT` listener per compound form) a field hidden for the
 *   submitted value keeps its current value; `secret: true` drops a typed value and keeps only an explicit `clear`.
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class VisibleWhenExtension extends AbstractTypeExtension
{
    public const OPTION = 'visible_when';

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION, null);
        $resolver->setAllowedTypes(self::OPTION, ['null', 'array']);
        $resolver->setNormalizer(self::OPTION, static function (Options $options, ?array $value): ?array {
            if ($value === null) {
                return null;
            }

            try {
                return VisibleWhenRules::normalize($value);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidOptionsException($exception->getMessage(), 0, $exception);
            }
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['compound'] === true) {
            // The children are not known yet (this extension runs before the concrete type's buildForm): the listener
            // looks for rules at submit time and returns at once without any.
            $builder->addEventListener(FormEvents::PRE_SUBMIT, VisibleWhenRules::keep(...));
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['compound'] !== true) {
            return;
        }
        foreach ($form->all() as $child) {
            $rule = $child->getConfig()->getOption(self::OPTION);
            if (!is_array($rule)) {
                continue;
            }
            $name = $child->getName();
            if ($rule['field'] === $name) {
                throw new LogicException(sprintf('Field "%s": visible_when.field cannot be the field itself.', $name));
            }
            $childView = $view->children[$name];
            /** @var array<string, mixed> $rowAttr */
            $rowAttr                     = $childView->vars['row_attr'] ?? [];
            $childView->vars['row_attr'] = array_merge($rowAttr, VisibleWhenRules::attributes($this->controllerName($view, $rule['field']), $rule['in']));
        }
    }

    /**
     * `name` attribute of the controlling input: the sibling's full name (`[]` for multiple checkboxes), else the name
     * it would have.
     */
    private function controllerName(FormView $view, string $field): string
    {
        $sibling = $view->children[$field] ?? null;
        if ($sibling instanceof FormView && is_string($sibling->vars['full_name'] ?? null)) {
            $name = $sibling->vars['full_name'];

            return ($sibling->vars['multiple'] ?? false) === true && ($sibling->vars['expanded'] ?? false) === true ? $name . '[]' : $name;
        }
        $root = $view->vars['full_name'] ?? '';

        return is_string($root) && $root !== '' ? $root . '[' . $field . ']' : $field;
    }
}
