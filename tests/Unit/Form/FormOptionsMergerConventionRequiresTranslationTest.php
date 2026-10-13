<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Opt-in {@code convention_requires_translation}: auto help / placeholder convention keys are only
 * applied when the key exists in the translator catalogue (fallback locales included).
 */
final class FormOptionsMergerConventionRequiresTranslationTest extends TestCase
{
    private function translator(): Translator
    {
        $translator = new Translator('es');
        $translator->addLoader('array', new ArrayLoader());
        $translator->setFallbackLocales(['en']);
        $translator->addResource('array', [
            'contact.name.label'       => 'Nombre',
            'contact.name.help'        => 'Tu nombre completo',
            'contact.name.placeholder' => 'Ej. Ana',
        ], 'es', 'forms');
        $translator->addResource('array', [
            'contact.email.help'        => 'We never share it',
            'contact.email.placeholder' => 'you@example.com',
        ], 'en', 'forms');
        $translator->addResource('array', [
            'contact.phone.help' => 'Messages domain only',
        ], 'es', 'messages');

        return $translator;
    }

    /**
     * @param array<string, mixed> $profileOverrides
     */
    private function merger(?TranslatorInterface $translator, bool $flag = true, array $profileOverrides = []): FormOptionsMerger
    {
        return new FormOptionsMerger(
            [
                'default' => $profileOverrides + [
                    'translation_domain'              => 'forms',
                    'convention_requires_translation' => $flag,
                    'defaults'                        => ['attr' => [], 'row_attr' => []],
                    'field_types'                     => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
            translator: $translator,
        );
    }

    public function testKeysPresentInCatalogueAreApplied(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'name', TextType::class);

        self::assertSame('contact.name.help', $options['help']);
        self::assertSame('contact.name.placeholder', $options['attr']['placeholder']);
    }

    public function testKeysAbsentFromCatalogueAreNotApplied(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'subject', TextType::class);

        self::assertArrayNotHasKey('help', $options);
        self::assertArrayNotHasKey('placeholder', $options['attr']);
    }

    public function testKeysOnlyInFallbackLocaleAreApplied(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'email', TextType::class);

        self::assertSame('contact.email.help', $options['help']);
        self::assertSame('contact.email.placeholder', $options['attr']['placeholder']);
    }

    public function testCurrentTranslatorLocaleIsUsedOnEveryResolve(): void
    {
        $translator = $this->translator();
        $merger     = $this->merger($translator);
        $translator->addResource('array', ['contact.city.help' => 'Ville'], 'fr', 'forms');

        self::assertArrayNotHasKey('help', $merger->resolve('contact', 'city', TextType::class));

        $translator->setLocale('fr');
        self::assertSame('contact.city.help', $merger->resolve('contact', 'city', TextType::class)['help']);
    }

    public function testKeyInAnotherDomainDoesNotCount(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'phone', TextType::class);

        self::assertArrayNotHasKey('help', $options);
    }

    public function testFieldTranslationDomainOverrideIsUsedForLookup(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'phone', TextType::class, [
            'translation_domain' => 'messages',
        ]);

        self::assertSame('contact.phone.help', $options['help']);
        self::assertArrayNotHasKey('placeholder', $options['attr']);
    }

    public function testTranslationDomainFalseSkipsConventionKeys(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'name', TextType::class, [
            'translation_domain' => false,
        ]);

        self::assertArrayNotHasKey('help', $options);
        self::assertArrayNotHasKey('placeholder', $options['attr']);
        self::assertSame('contact.name.label', $options['label']);
    }

    public function testFlagOffKeepsConventionKeysEvenWhenMissing(): void
    {
        $options = $this->merger($this->translator(), false)->resolve('contact', 'subject', TextType::class);

        self::assertSame('contact.subject.help', $options['help']);
        self::assertSame('contact.subject.placeholder', $options['attr']['placeholder']);
    }

    public function testFlagMissingFromProfileDefaultsToOldBehaviour(): void
    {
        $merger = new FormOptionsMerger(
            ['default' => ['translation_domain' => 'forms', 'defaults' => ['attr' => [], 'row_attr' => []], 'field_types' => []]],
            'default',
            new ConstraintDefinitionFactory(),
            translator: $this->translator(),
        );

        $options = $merger->resolve('contact', 'subject', TextType::class);

        self::assertSame('contact.subject.help', $options['help']);
        self::assertSame('contact.subject.placeholder', $options['attr']['placeholder']);
    }

    public function testLabelIsNeverAffected(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'subject', TextType::class);

        self::assertSame('contact.subject.label', $options['label']);
    }

    public function testExplicitOptionsWinOverMissingTranslation(): void
    {
        $options = $this->merger($this->translator())->resolve('contact', 'subject', TextType::class, [
            'help'        => 'contact.subject.help',
            'placeholder' => 'contact.subject.placeholder',
        ]);

        self::assertSame('contact.subject.help', $options['help']);
        self::assertSame('contact.subject.placeholder', $options['attr']['placeholder']);
    }

    public function testConfiguredHelpWinsOverMissingTranslation(): void
    {
        $merger = $this->merger($this->translator(), true, [
            'translation_domain'              => 'forms',
            'convention_requires_translation' => true,
            'defaults'                        => ['attr' => [], 'row_attr' => []],
            'field_types'                     => ['text' => ['help' => 'shared.text_help']],
        ]);

        self::assertSame('shared.text_help', $merger->resolve('contact', 'subject', TextType::class)['help']);
    }

    public function testAutoHelpAndPlaceholderOffStayOff(): void
    {
        $options = $this->merger($this->translator(), true, [
            'translation_domain'              => 'forms',
            'convention_requires_translation' => true,
            'auto_help'                       => false,
            'auto_placeholder'                => false,
            'defaults'                        => ['attr' => [], 'row_attr' => []],
            'field_types'                     => [],
        ])->resolve('contact', 'name', TextType::class);

        self::assertArrayNotHasKey('help', $options);
        self::assertArrayNotHasKey('placeholder', $options['attr']);
    }

    public function testNoEffectWithoutTranslator(): void
    {
        $options = $this->merger(null)->resolve('contact', 'subject', TextType::class);

        self::assertSame('contact.subject.help', $options['help']);
        self::assertSame('contact.subject.placeholder', $options['attr']['placeholder']);
    }

    public function testNoEffectWithTranslatorWithoutCatalogues(): void
    {
        $options = $this->merger(new IdentityTranslator())->resolve('contact', 'subject', TextType::class, [
            'translation_domain' => false,
        ]);

        self::assertSame('contact.subject.help', $options['help']);
        self::assertSame('contact.subject.placeholder', $options['attr']['placeholder']);
    }
}
