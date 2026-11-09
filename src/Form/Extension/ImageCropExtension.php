<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\ImageCropAttributes;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

/**
 * Adds the `crop` option to FileType and every type built on it (e.g. UX Dropzone): `false` (default, nothing printed) |
 * `true` (UiKit's defaults: a square, 1024 px, format auto) | an array of {@see ImageCropAttributes} options, e.g.
 * `['shape' => 'circle', 'format' => 'auto', 'max' => 512, 'max_bytes' => 190_000]`.
 *
 * The file input then carries UiKit 2.43's image crop attributes (`data-nowo-ui-image-crop*`). Refused with `multiple`
 * (the crop is one image) and together with UiKit's own `nowo_ui_image_crop` option (use one of them); the field's own
 * `attr` wins over the printed attributes. Options are validated when the form is built (InvalidOptionsException),
 * through UiKit's rules when it delegates to UiKit and FormKit's own copy otherwise.
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class ImageCropExtension extends AbstractTypeExtension
{
    public const OPTION = 'crop';

    /**
     * UiKit's own FileType option for the same contract; a field sets one or the other, never both.
     */
    public const UI_KIT_OPTION = 'nowo_ui_image_crop';

    /**
     * @param bool|null $uiKit null = delegate to UiKit ^2.43 when installed, false = FormKit's own printer, true = UiKit when its class exists
     */
    public function __construct(
        private readonly ?TranslatorInterface $translator = null,
        private readonly ?bool $uiKit = null,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION, false);
        $resolver->setAllowedTypes(self::OPTION, ['bool', 'array']);
        $uiKit = $this->uiKit;
        $resolver->setNormalizer(self::OPTION, static function (Options $options, bool|array $value) use ($uiKit): array|false {
            if ($value === false) {
                return false;
            }
            if (($options['multiple'] ?? false) === true) {
                throw new InvalidOptionsException(sprintf('"%s" crops one image: it cannot be used with "multiple".', self::OPTION));
            }
            // Both extensions merge their attributes under the field's attr: together they would print a mix of the two
            // configs that neither validated.
            if ($options->offsetExists(self::UI_KIT_OPTION) && $options[self::UI_KIT_OPTION] !== false) {
                throw new InvalidOptionsException(sprintf('"%s" and "%s" cannot both be set on one field: use one of them.', self::OPTION, self::UI_KIT_OPTION));
            }
            $value = $value === true ? [] : $value;

            try {
                // The same path that prints the attributes in finishView(): UiKit's rules when it delegates.
                ImageCropAttributes::validate($value, $uiKit);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidOptionsException(sprintf('"%s": %s', self::OPTION, $exception->getMessage()), 0, $exception);
            }

            return $value;
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<mixed>|false $config */
        $config = $options[self::OPTION];
        if ($config === false) {
            return;
        }

        /** @var array<string, mixed> $attr */
        $attr               = $view->vars['attr'] ?? [];
        $view->vars['attr'] = $attr + ImageCropAttributes::attributes($config, $this->translator, $this->uiKit);
    }
}
