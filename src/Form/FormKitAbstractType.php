<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use function is_array;

/**
 * Base form type that uses FormKitTrait with option merger and type map injected.
 *
 * Extend this type and use mergeFieldOptions() / addField() in buildForm()
 * to get cascading options and convention-based label/placeholder/help.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
/**
 * @extends AbstractType<mixed>
 */
abstract class FormKitAbstractType extends AbstractType
{
    use FormKitTrait;

    public function __construct(FormOptionsMerger $formOptionsMerger, FormTypeMap $formTypeMap)
    {
        $this->formOptionsMerger = $formOptionsMerger;
        $this->formTypeMap       = $formTypeMap;
    }

    /**
     * Options for fields whose visible copy is supplied by Twig (e.g. {@code placeholder: 'key'|trans}).
     *
     * Symfony's form theme re-runs {@code |trans} on {@code attr.placeholder} / {@code title}
     * using the field {@code translation_domain}. Pre-translated strings must therefore use
     * {@code translation_domain: false}, otherwise they are looked up again in the form domain.
     *
     * @return array{label: false, help: false, placeholder: false, translation_domain: false}
     */
    protected function twigOwnedChromeOptions(): array
    {
        return [
            'label'              => false,
            'help'               => false,
            'placeholder'        => false,
            'translation_domain' => false,
        ];
    }

    /**
     * Add a {@see ChoiceType} field using the builder bound by {@see withBuilder()} and restore the
     * ChoiceType empty option after the FormKit merge.
     *
     * FormKit moves the root {@code placeholder} option to {@code attr.placeholder}; ChoiceType needs it
     * as the root empty-option instead. Pass {@code placeholder} for a custom empty-option key, omit it
     * to use {@code {blockPrefix}.{field_snake}.placeholder}, or pass {@code false} to skip restoring the empty option (no root {@code placeholder} is set).
     * Pass {@code label} / {@code help} as {@code false} when Twig owns the chrome (e.g. confirm dialogs).
     * When the empty option is kept, {@code translation_domain} defaults to {@code form} unless given in $options.
     *
     * @param array<string, mixed> $options
     *
     * @throws LogicException when called outside withBuilder()
     */
    protected function addChoiceWithFormPlaceholder(string $name, array $options): void
    {
        $emptyOption = $options['placeholder'] ?? null;
        unset($options['placeholder']);
        $options['placeholder'] = false;

        $merged = $this->mergeFieldOptions($name, 'choice', $options);
        if ($emptyOption !== false) {
            if ($emptyOption === null) {
                $fieldSnake  = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
                $emptyOption = $this->getBlockPrefix() . '.' . $fieldSnake . '.placeholder';
            }
            $merged['placeholder']        = $emptyOption;
            $merged['translation_domain'] = $options['translation_domain'] ?? 'form';
            if (isset($merged['attr']) && is_array($merged['attr'])) {
                unset($merged['attr']['placeholder']);
            }
        }

        $this->boundBuilder()->add($name, ChoiceType::class, $merged);
    }
}
