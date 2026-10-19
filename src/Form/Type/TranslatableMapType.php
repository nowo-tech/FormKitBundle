<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function array_filter;
use function array_merge;
use function array_values;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function strtoupper;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * A `{locale: text}` map (Doctrine `json` column, translatable label / title / slug) edited as one field
 * per locale, rendered as locale tabs (`locale_tabs` is on by default; UiKit 2.5+ renders them when installed).
 *
 *   $builder->add('labels', TranslatableMapType::class, [
 *       'required_locales' => ['es'],
 *       'entry_type'       => TextareaType::class,
 *   ]);
 *
 * Options:
 * - `locales` (default `nowo_form_kit.locale_tabs.locales`, i.e. `framework.enabled_locales`, else the default locale)
 * - `default_locale` (default `nowo_form_kit.locale_tabs.default_locale` / `kernel.default_locale` when listed, else the first locale)
 * - `required_locales` (default []): those children are `required` and get a NotBlank constraint
 * - `entry_type` (TextType) / `entry_options` (merged into every child; label default `ES`, …)
 * - `remove_empty` (true): blank values are dropped from the submitted map
 * - `input` (`array` | `json`): the model is an array (default) or a JSON string
 *
 * Keys of the model that are not in `locales` are kept on submit (e.g. a locale disabled later).
 *
 * Stateless — safe for FrankenPHP worker mode.
 *
 * @extends AbstractType<array<string, string>|string|null>
 */
final class TranslatableMapType extends AbstractType
{
    /**
     * @param list<string> $locales configured locales
     */
    public function __construct(
        private readonly array $locales = [],
        private readonly ?string $defaultLocale = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<string> $locales */
        $locales = $options['locales'];
        /** @var list<string> $required */
        $required = $options['required_locales'];
        /** @var array<string, mixed> $entryOptions */
        $entryOptions = $options['entry_options'];
        /** @var class-string $entryType */
        $entryType = $options['entry_type'];

        foreach ($locales as $locale) {
            $isRequired = in_array($locale, $required, true);
            $builder->add($locale, $entryType, array_merge([
                'label'              => strtoupper($locale),
                'translation_domain' => false,
                'required'           => $isRequired,
                'constraints'        => $isRequired ? [new NotBlank()] : [],
            ], $entryOptions));
        }

        $removeEmpty = (bool) $options['remove_empty'];
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (mixed $map): array => is_array($map) ? $map : [],
            static fn (mixed $map): array => !is_array($map) ? [] : ($removeEmpty ? array_filter($map, static fn (mixed $v): bool => $v !== null && $v !== '') : $map),
        ));

        if ($options['input'] === 'json') {
            // Model transformers are prepended: this one runs first on the way in, last on the way out.
            $builder->addModelTransformer(new CallbackTransformer(
                static function (mixed $json): array {
                    if (!is_string($json) || $json === '') {
                        return [];
                    }
                    $decoded = json_decode($json, true);

                    return is_array($decoded) ? $decoded : [];
                },
                static fn (mixed $map): ?string => is_array($map) && $map !== [] ? json_encode($map, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!is_array($view->vars['locale_tabs'] ?? null)) {
            return;
        }
        $view->vars['locale_tabs'] += [
            'locales'          => $options['locales'],
            'default_locale'   => $options['default_locale'],
            'required_locales' => $options['required_locales'],
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $configured = $this->locales !== [] ? array_values($this->locales) : [$this->defaultLocale ?? 'en'];

        $resolver->setDefaults([
            'locales'          => $configured,
            'default_locale'   => null,
            'required_locales' => [],
            'entry_type'       => TextType::class,
            'entry_options'    => [],
            'remove_empty'     => true,
            'input'            => 'array',
            'locale_tabs'      => true,
            // A parent that is not required makes every child optional (Form::isRequired()).
            'required'         => static fn (Options $options): bool => $options['required_locales'] !== [],
            'empty_data'       => [],
            'error_bubbling'   => false,
        ]);
        $resolver->setAllowedTypes('locales', 'string[]');
        $resolver->setAllowedTypes('default_locale', ['null', 'string']);
        $resolver->setAllowedTypes('required_locales', 'string[]');
        $resolver->setAllowedTypes('entry_type', 'string');
        $resolver->setAllowedTypes('entry_options', 'array');
        $resolver->setAllowedTypes('remove_empty', 'bool');
        $resolver->setAllowedValues('input', ['array', 'json']);
        $resolver->setNormalizer('locales', static fn (Options $options, array $locales): array => array_values($locales));
        $defaultLocale = $this->defaultLocale;
        $resolver->setNormalizer('default_locale', static function (Options $options, ?string $value) use ($defaultLocale): string {
            /** @var list<string> $locales */
            $locales = $options['locales'];
            if ($value !== null && $value !== '') {
                return $value;
            }

            return $defaultLocale !== null && in_array($defaultLocale, $locales, true) ? $defaultLocale : ($locales[0] ?? '');
        });
    }

    public function getBlockPrefix(): string
    {
        return 'nowo_translatable_map';
    }
}
