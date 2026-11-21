<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: named config profile via #[FormKitConfig('wide')] (US-02 / US-07).
 *
 * Uses wider UiKit input classes from the wide profile in nowo_form_kit.yaml.
 */
#[FormKitConfig('wide')]
final class NamedConfigDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $row = ['row_attr' => ['class' => 'nowo-ui-form-row']];

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
                // No visible label (the point of this field), so the input carries its name.
                'attr'        => ['aria-label' => 'Internal code'],
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
