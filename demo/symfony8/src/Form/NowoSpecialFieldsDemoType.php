<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\NowoSpecialFieldsDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\IconSelectorBundle\Form\IconSelectorType;
use Nowo\PhoneInputBundle\Form\ValueFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Showcases Nowo ecosystem special fields via Form Kit optional helpers.
 *
 * Passwords: toggle-only, strength-only, and combined (PasswordStrengthType + PasswordToggleBundle).
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

        $this->withBuilder($builder, function () use ($row, $widgetRoot): void {
            $this->addOtpField('verificationCode', $widgetRoot([
                'length'       => 6,
                'numeric_only' => true,
            ]));

            $this->addPhoneField('mobilePhone', $widgetRoot([
                'value_format'            => ValueFormat::CONCATENATED,
                'country_prefix_selector' => true,
                'required'                => false,
            ]));

            $this->addPasswordToggleField('secretPassword', $row([
                'required' => false,
                'toggle'   => true,
            ]));

            $this->addPasswordStrengthField('strengthOnlyPassword', $row([
                'required'            => false,
                'ui_framework'        => 'bootstrap5',
                'use_password_toggle' => false,
            ]));

            $this->addPasswordStrengthField('combinedPassword', $row([
                'required'            => false,
                'ui_framework'        => 'bootstrap5',
                'use_password_toggle' => true,
                'toggle'              => true,
            ]));

            $this->addIconSelectorField('appIcon', $widgetRoot([
                'mode'        => IconSelectorType::MODE_TOM_SELECT,
                'icon_sets'   => ['heroicons', 'bootstrap-icons'],
                'required'    => false,
                'placeholder' => 'nowo_special_fields_demo.app_icon.placeholder',
            ]));

            $this->addTagInputField('keywords', [
                'row_attr'  => ['class' => 'mb-3'],
                'attr'      => ['class' => ''],
                'required'  => false,
                'max_tags'  => 8,
                'whitelist' => ['php', 'symfony', 'twig', 'forms'],
            ]);

            $this->addTiptapEditorField('articleBody', $widgetRoot([
                'config'      => 'simple',
                'required'    => false,
                'placeholder' => 'tiptap_placeholder',
            ]));

            $this->addCkeditor5EditorField('pageContent', $widgetRoot([
                'config'      => 'simple',
                'required'    => false,
                'placeholder' => 'ckeditor5_placeholder',
            ]));

            $this->addSlideToConfirmField('confirmSlide', $widgetRoot([
                'profile'           => 'gate',
                'submit_on_confirm' => false,
                'required'          => false,
                'mapped'            => true,
            ]));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NowoSpecialFieldsDemoData::class,
        ]);
    }
}
