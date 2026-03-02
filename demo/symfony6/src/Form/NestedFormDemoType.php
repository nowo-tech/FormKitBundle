<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\ContactWithAddress;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo form with nested AddressType. Convention: nested_form.* for top-level,
 * address.* for the embedded form (via AddressType).
 */
class NestedFormDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFromArray($builder, [
            'fullName' => TextType::class,
            'email' => EmailType::class,
            'address' => AddressType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactWithAddress::class,
        ]);
    }
}
