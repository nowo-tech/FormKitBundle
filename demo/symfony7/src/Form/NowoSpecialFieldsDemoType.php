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
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->addWithDefaults($builder, 'verificationCode', OtpType::class, array_merge($rowHalf, [
            'length'       => 6,
            'numeric_only' => true,
        ]));

        $this->addWithDefaults($builder, 'mobilePhone', PhoneType::class, array_merge($rowHalf, [
            'value_format'            => ValueFormat::CONCATENATED,
            'country_prefix_selector' => true,
            'required'                => false,
        ]));

        $this->addWithDefaults($builder, 'secretPassword', TogglePasswordType::class, array_merge($rowHalf, [
            'required' => false,
        ]));

        $this->addWithDefaults($builder, 'accountPassword', PasswordStrengthType::class, array_merge($rowFull, [
            'required' => false,
        ]));

        $this->addWithDefaults($builder, 'appIcon', IconSelectorType::class, array_merge($rowFull, [
            'mode'      => IconSelectorType::MODE_TOM_SELECT,
            'icon_sets' => ['heroicons', 'bootstrap-icons'],
            'required'  => false,
        ]));

        $this->addWithDefaults($builder, 'articleBody', TiptapEditorType::class, array_merge($rowFull, [
            'config'   => 'simple',
            'required' => false,
        ]));

        $this->addWithDefaults($builder, 'pageContent', Ckeditor5EditorType::class, array_merge($rowFull, [
            'config'   => 'simple',
            'required' => false,
        ]));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NowoSpecialFieldsDemoData::class,
        ]);
    }
}
