<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs\HostPaths;

use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Standard host form: binds to the {@see Article} entity, locale tabs on a Collection indexed by locale. */
final class ArticleType extends FormKitAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addTextField('code');
        });
        $builder->add('translations', CollectionType::class, [
            'entry_type'    => TranslationEntryType::class,
            'entry_options' => ['data_class' => ArticleTranslation::class, 'error_bubbling' => false],
            'locale_tabs'   => ['default_locale' => 'es', 'required_locales' => ['es']],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Article::class]);
    }
}
