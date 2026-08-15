<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Override;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Rootless GET search form ({@code q}) for dashboard / list filters.
 *
 * Uses FormKit profile {@code filter} via {@see AbstractGetFilterType}.
 * Callers pass {@code attr.placeholder} already translated when needed
 * ({@code placeholder: false} + {@code translation_domain: false}).
 *
 * Requires {@code search} in {@code FormTypeMap} (built-in {@see SearchType}).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class SearchQueryType extends AbstractGetFilterType
{
    /** @param FormBuilderInterface<mixed> $builder */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->withBuilder($builder, function () use ($options): void {
            $this->addNamedField('q', 'search', [
                'empty_data'         => '',
                'data'               => (string) ($options['q'] ?? ''),
                'placeholder'        => false,
                'help'               => false,
                'translation_domain' => false,
                'attr'               => array_merge(['type' => 'search'], $options['input_attr']),
            ]);
        });
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'q'          => '',
            'input_attr' => [],
        ]);
        $resolver->setAllowedTypes('q', 'string');
        $resolver->setAllowedTypes('input_attr', 'array');
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return '';
    }
}
