<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * One translation entity of the locale tabs demo (mode "entries"): locale (hidden input in its tab), title, body.
 *
 * @extends AbstractType<array<string, string|null>>
 */
final class LocaleTabsEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', HiddenType::class)
            ->add('title', TextType::class, ['label' => 'Title', 'translation_domain' => false, 'constraints' => [new NotBlank()]])
            ->add('body', TextareaType::class, ['label' => 'Body', 'translation_domain' => false, 'required' => false, 'attr' => ['rows' => 2]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['error_bubbling' => false]);
    }
}
