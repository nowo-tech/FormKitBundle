<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\AutocompleteDemoData;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Symfony UX Autocomplete: {@see ChoiceType} with {@code autocomplete => true} (Tom Select, local choices).
 * Uses {@see FormOptionsTrait::addAutocompleteField()} via {@see FormOptionsTrait::boundBuilder()}.
 */
final class AutocompleteDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowHalf = ['row_attr' => ['class' => 'col-12 col-md-6 mb-3']];
        $rowFull = ['row_attr' => ['class' => 'col-12 mb-3']];

        $this->withBuilder($builder, function () use ($rowHalf, $rowFull): void {
            $this->addAutocompleteField($this->boundBuilder(), 'topic', ChoiceType::class, array_merge($rowHalf, [
                'required' => false,
                'choices'  => [
                    'Symfony'        => 'symfony',
                    'API Platform'   => 'api_platform',
                    'Doctrine ORM'   => 'doctrine',
                    'Twig'           => 'twig',
                    'Webpack Encore' => 'encore',
                    'Asset Mapper'   => 'asset_mapper',
                ],
                'autocomplete' => true,
            ]));

            $this->addAutocompleteField($this->boundBuilder(), 'skills', ChoiceType::class, array_merge($rowFull, [
                'required' => false,
                'choices'  => [
                    'PHP'        => 'php',
                    'JavaScript' => 'js',
                    'TypeScript' => 'ts',
                    'SQL'        => 'sql',
                    'Docker'     => 'docker',
                    'HTTP'       => 'http',
                ],
                'multiple'     => true,
                'autocomplete' => true,
            ]));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutocompleteDemoData::class,
        ]);
    }
}
