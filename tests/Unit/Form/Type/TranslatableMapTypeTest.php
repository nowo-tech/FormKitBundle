<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Validation;

use function array_keys;

final class TranslatableMapTypeTest extends TestCase
{
    public function testOneChildPerConfiguredLocaleAndTabsOnByDefault(): void
    {
        $form = $this->factory(['es', 'en'], 'en')->createNamed('labels', TranslatableMapType::class, ['es' => 'Hola']);

        self::assertSame(['es', 'en'], array_keys($form->all()));
        self::assertSame('Hola', $form->get('es')->getData());
        self::assertSame('ES', $form->get('es')->getConfig()->getOption('label'));
        self::assertFalse($form->isRequired());

        $view = $form->createView();
        self::assertSame(['locales' => ['es', 'en'], 'default_locale' => 'en', 'required_locales' => []], $view->vars['locale_tabs']);
        self::assertContains('nowo_translatable_map', $view->vars['block_prefixes']);
        self::assertContains(LocaleTabsExtension::BLOCK_PREFIX, $view->vars['block_prefixes']);
    }

    public function testSubmitKeepsUnknownLocalesAndDropsBlankValues(): void
    {
        $form = $this->factory(['es', 'en'])->createNamed('labels', TranslatableMapType::class, ['es' => 'Hola', 'en' => 'Hi', 'de' => 'Hallo']);
        $form->submit(['es' => 'Hola!', 'en' => '']);

        self::assertTrue($form->isValid());
        self::assertSame(['es' => 'Hola!', 'de' => 'Hallo'], $form->getData());

        $keep = $this->factory(['es', 'en'])->createNamed('labels', TranslatableMapType::class, null, ['remove_empty' => false]);
        $keep->submit(['es' => 'x', 'en' => '']);
        self::assertSame(['es' => 'x', 'en' => null], $keep->getData());
    }

    public function testRequiredLocalesAreRequiredAndNotBlank(): void
    {
        $form = $this->factory(['es', 'en'])->createNamed('labels', TranslatableMapType::class, null, ['required_locales' => ['es'], 'entry_type' => TextareaType::class, 'entry_options' => ['attr' => ['rows' => 2]]]);

        self::assertTrue($form->isRequired());
        self::assertTrue($form->get('es')->isRequired());
        self::assertFalse($form->get('en')->isRequired());
        self::assertSame(['rows' => 2], $form->get('en')->getConfig()->getOption('attr'));

        $form->submit(['es' => '', 'en' => 'Hi']);
        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('es')->getErrors());
        self::assertSame(['es'], $form->createView()->vars['locale_tabs']['required_locales']);
    }

    public function testJsonInput(): void
    {
        $form = $this->factory(['es', 'en'])->createNamed('labels', TranslatableMapType::class, '{"es":"Hola","xx":"keep"}', ['input' => 'json']);
        self::assertSame('Hola', $form->get('es')->getData());
        $form->submit(['es' => 'Hola/ñ', 'en' => '']);
        self::assertSame('{"es":"Hola/ñ","xx":"keep"}', $form->getData());

        $empty = $this->factory(['es'])->createNamed('labels', TranslatableMapType::class, 'not json', ['input' => 'json']);
        self::assertNull($empty->get('es')->getData());
        $empty->submit(['es' => '']);
        self::assertNull($empty->getData());

        $null = $this->factory(['es'])->createNamed('labels', TranslatableMapType::class, null, ['input' => 'json']);
        self::assertNull($null->get('es')->getData());
    }

    public function testLocaleDefaultsAndOptions(): void
    {
        // No configured locales: the default locale (else en).
        self::assertSame(['fr'], array_keys($this->factory([], 'fr')->createNamed('l', TranslatableMapType::class)->all()));
        self::assertSame(['en'], array_keys($this->factory()->createNamed('l', TranslatableMapType::class)->all()));

        // Configured default not listed: the first locale; explicit default and locales win; locale_tabs false.
        $view = $this->factory(['es', 'en'], 'de')->createNamed('l', TranslatableMapType::class)->createView();
        self::assertSame('es', $view->vars['locale_tabs']['default_locale']);
        $view = $this->factory(['es', 'en'], 'de')->createNamed('l', TranslatableMapType::class, null, ['locales' => ['it', 'pt'], 'default_locale' => 'pt', 'locale_tabs' => ['tabs_id' => 'x']])->createView();
        self::assertSame(['tabs_id' => 'x', 'locales' => ['it', 'pt'], 'default_locale' => 'pt', 'required_locales' => []], $view->vars['locale_tabs']);
        self::assertArrayNotHasKey('locale_tabs', $this->factory(['es'])->createNamed('l', TranslatableMapType::class, null, ['locale_tabs' => false])->createView()->vars);
        self::assertSame('', $this->factory(['es'])->createNamed('l', TranslatableMapType::class, null, ['locales' => []])->getConfig()->getOption('default_locale'));

        // 2.13.1: a default_locale that is not listed falls back (configured default when listed, else the first).
        self::assertSame('en', $this->factory(['es', 'en'], 'en')->createNamed('l', TranslatableMapType::class, null, ['default_locale' => 'fr'])->getConfig()->getOption('default_locale'));
        $view = $this->factory(['es', 'en'], 'de')->createNamed('l', TranslatableMapType::class, null, ['locales' => ['it', 'pt'], 'default_locale' => 'es'])->createView();
        self::assertSame('it', $view->vars['locale_tabs']['default_locale']);
        self::assertSame(['it', 'pt'], array_keys($view->children));

        $this->expectException(InvalidOptionsException::class);
        $this->factory(['es'])->createNamed('l', TranslatableMapType::class, null, ['input' => 'xml']);
    }

    /**
     * @param list<string> $locales
     */
    private function factory(array $locales = [], ?string $default = null): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addTypeExtension(new LocaleTabsExtension())
            ->addType(new TranslatableMapType($locales, $default))
            ->getFormFactory();
    }
}
