<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: named config profile via #[FormKitConfig('bootstrap')] (US-02 / US-07).
 *
 * Uses larger form-control classes from the bootstrap profile in nowo_form_kit.yaml.
 */
#[FormKitConfig('bootstrap')]
final class NamedConfigDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $row = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->withBuilder($builder, function () use ($row): void {
            $this->addTextField('title', $row);
            $this->addTextareaField('summary', array_merge($row, [
                'required' => false,
            ]));
            $this->addTextField('internal_code', array_merge($row, [
                'label'       => false,
                'placeholder' => false,
                'help'        => false,
                'required'    => false,
            ]));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
