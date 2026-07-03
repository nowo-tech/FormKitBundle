<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\NowoSpecialFieldsDemoData;
use Nowo\Ckeditor5EditorBundle\Form\Ckeditor5EditorType;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\IconSelectorBundle\Form\IconSelectorType;
use Nowo\OtpInputBundle\Form\OtpType;
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordToggleBundle\Form\Type\PasswordType as TogglePasswordType;
use Nowo\PhoneInputBundle\Form\Type\PhoneType;
use Nowo\PhoneInputBundle\Form\ValueFormat;
use Nowo\TiptapEditorBundle\Form\TiptapEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Showcases Nowo ecosystem special fields integrated with Form Kit conventions.
 */
final class NowoSpecialFieldsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Grid columns live in the Twig template; row_attr only adds vertical spacing.
        $row = static fn (array $options = []): array => array_merge(['row_attr' => ['class' => 'mb-3']], $options);

        // Compound / custom widgets must not inherit global form-control on the widget root.
        $widgetRoot = static fn (array $options = []): array => array_merge([
            'row_attr'    => ['class' => 'mb-3'],
            'attr'        => ['class' => ''],
            'placeholder' => false,
        ], $options);

        $this->addWithDefaults($builder, 'verificationCode', OtpType::class, $widgetRoot([
            'length'       => 6,
            'numeric_only' => true,
        ]));

        $this->addWithDefaults($builder, 'mobilePhone', PhoneType::class, $widgetRoot([
            'value_format'            => ValueFormat::CONCATENATED,
            'country_prefix_selector' => true,
            'required'                => false,
        ]));

        $this->addWithDefaults($builder, 'secretPassword', TogglePasswordType::class, $row([
            'required' => false,
        ]));

        $this->addWithDefaults($builder, 'accountPassword', PasswordStrengthType::class, $row([
            'required'     => false,
            'ui_framework' => 'bootstrap5',
        ]));

        $this->addWithDefaults($builder, 'appIcon', IconSelectorType::class, $widgetRoot([
            'mode'        => IconSelectorType::MODE_TOM_SELECT,
            'icon_sets'   => ['heroicons', 'bootstrap-icons'],
            'required'    => false,
            'placeholder' => 'nowo_special_fields_demo.app_icon.placeholder',
        ]));

        $this->addWithDefaults($builder, 'articleBody', TiptapEditorType::class, $widgetRoot([
            'config'      => 'simple',
            'required'    => false,
            'placeholder' => 'tiptap_placeholder',
        ]));

        $this->addWithDefaults($builder, 'pageContent', Ckeditor5EditorType::class, $widgetRoot([
            'config'      => 'simple',
            'required'    => false,
            'placeholder' => 'ckeditor5_placeholder',
        ]));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NowoSpecialFieldsDemoData::class,
        ]);
    }
}
