<?php

declare(strict_types=1);

namespace App\Form;

use App\Demo\DemoTranslationLocales;
use App\Model\DemoTranslatableItem;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Demo form: translatable fields per locale via the bundle's TranslationsFormsType (wraps A2lix).
 * Uses buildFormFromArray; TranslationItemType also uses buildFormFromArray for title/description.
 */
class TranslationsDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTranslations($builder, [
            'form_type' => TranslationItemType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemoTranslatableItem::class,
        ]);
    }

    /**
     * Aligns A2lix enabled_locales with the same random subset as {@see DemoTranslatableItem::forLocales()}.
     *
     * @param array<string, mixed> $options
     *
     * @return array{default_locale: string, enabled_locales: list<string>}
     */
    protected function resolveFormKitTranslationsLocaleContext(array $options): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return [
                'default_locale'  => 'en',
                'enabled_locales' => ['en', 'es', 'fr', 'de'],
            ];
        }

        return DemoTranslationLocales::forSession($request->getSession());
    }
}
