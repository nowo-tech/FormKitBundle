<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\ConditionalFieldsDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_array;
use function is_object;
use function is_string;

/**
 * Demo: account_type radios + PRE_SET_DATA / PRE_SUBMIT add company_name or first/last name.
 *
 * @see docs/USAGE.md "Conditional fields"
 */
final class ConditionalFieldsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $row = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->withBuilder($builder, function () use ($row): void {
            $this->addChoiceRadiosField('account_type', array_merge($row, [
                'choices' => [
                    'Individual' => 'individual',
                    'Company'    => 'company',
                ],
            ]));
        });

        $adapt = function (FormInterface $form, ?string $accountType) use ($row): void {
            foreach (['company_name', 'first_name', 'last_name'] as $name) {
                if ($form->has($name)) {
                    $form->remove($name);
                }
            }

            if ($accountType === 'company') {
                $form->add(
                    'company_name',
                    TextType::class,
                    $this->resolveFieldOptions('company_name', TextType::class, $row),
                );

                return;
            }

            $form->add(
                'first_name',
                TextType::class,
                $this->resolveFieldOptions('first_name', TextType::class, $row),
            );
            $form->add(
                'last_name',
                TextType::class,
                $this->resolveFieldOptions('last_name', TextType::class, $row),
            );
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($adapt): void {
            $adapt($event->getForm(), $this->resolveAccountType($event->getData()));
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($adapt): void {
            $data = $event->getData();
            $type = is_array($data) ? ($data['account_type'] ?? null) : null;
            $adapt($event->getForm(), is_string($type) ? $type : 'individual');
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConditionalFieldsDemoData::class,
        ]);
    }

    private function resolveAccountType(mixed $data): string
    {
        if (is_object($data) && isset($data->account_type) && is_string($data->account_type)) {
            return $data->account_type;
        }

        if (is_array($data) && isset($data['account_type']) && is_string($data['account_type'])) {
            return $data['account_type'];
        }

        return 'individual';
    }
}
