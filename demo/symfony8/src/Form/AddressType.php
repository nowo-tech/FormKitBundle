<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\Address;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Nested form type: address (street, number, floor, postal code, city, province).
 * Convention: address.* (e.g. address.street.label).
 */
class AddressType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFromArray($builder, [
            'street' => TextType::class,
            'number' => TextType::class,
            'floor' => TextType::class,
            'postalCode' => TextType::class,
            'city' => TextType::class,
            'province' => TextType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
