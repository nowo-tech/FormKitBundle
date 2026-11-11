<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** DDD host form: binds to {@see RegisterCustomerInput}, e-mail as a value object, locale tabs on DTOs. */
final class RegisterCustomerType extends FormKitAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addTextField('name');
            $this->addEmailField('email');
        });
        $builder->get('email')->addModelTransformer(new EmailAddressTransformer());
        $builder->add('translations', CollectionType::class, [
            'entry_type'    => TranslationEntryType::class,
            'entry_options' => ['data_class' => TranslationInput::class, 'error_bubbling' => false],
            'locale_tabs'   => ['default_locale' => 'es'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RegisterCustomerInput::class]);
    }
}
