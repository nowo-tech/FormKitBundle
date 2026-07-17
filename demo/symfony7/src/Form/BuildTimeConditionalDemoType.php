<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo: US-06 pattern 1 — build-time if on a known form option (no FormEvents).
 *
 * Pass account_mode = company|individual when creating the form.
 */
final class BuildTimeConditionalDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $row  = ['row_attr' => ['class' => 'col-12 mb-3']];
        $mode = (string) $options['account_mode'];

        $this->withBuilder($builder, function () use ($row, $mode): void {
            $this->addChoiceRadiosField('account_type', array_merge($row, [
                'choices' => [
                    'Individual' => 'individual',
                    'Company'    => 'company',
                ],
                'data'     => $mode,
                'disabled' => true,
            ]));

            if ($mode === 'company') {
                $this->addTextField('company_name', $row);
            } else {
                $this->addTextField('first_name', $row);
                $this->addTextField('last_name', $row);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => null,
            'account_mode' => 'individual',
        ]);
        $resolver->setAllowedValues('account_mode', ['individual', 'company']);
    }

    public function getBlockPrefix(): string
    {
        return 'build_time_conditional_demo';
    }
}
