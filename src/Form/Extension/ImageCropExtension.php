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
 * (the crop is one image); the field's own `attr` wins over the printed attributes. Options are validated when the
 * form is built (InvalidOptionsException).
 *
 * Stateless — safe for FrankenPHP worker mode.
 */
final class ImageCropExtension extends AbstractTypeExtension
{
    public const OPTION = 'crop';

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
        $resolver->setNormalizer(self::OPTION, static function (Options $options, bool|array $value): array|false {
            if ($value === false) {
                return false;
            }
            if (($options['multiple'] ?? false) === true) {
                throw new InvalidOptionsException(sprintf('"%s" crops one image: it cannot be used with "multiple".', self::OPTION));
            }
            $value = $value === true ? [] : $value;

            try {
                ImageCropAttributes::normalize($value);
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
