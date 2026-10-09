<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;

final class LocaleTabsThemeTest extends TestCase
{
    public function testThemeRendersOneAccessibleTabPerLocale(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['default_locale' => 'es', 'locale_labels' => ['es' => 'Spanish', 'en' => 'English']]]);

        $html = $this->render("{% form_theme form '@NowoFormKitBundle/form/locale_tabs_theme.html.twig' %}{{ form_row(form.slugs) }}", $form);

        self::assertStringContainsString('role="tablist" aria-label="Slugs"', $html);
        self::assertSame(2, substr_count($html, 'role="tab"'));
        self::assertSame(2, substr_count($html, 'role="tabpanel"'));
        self::assertStringContainsString('id="tab-root_slugs-locale-es"', $html);
        self::assertStringContainsString('aria-controls="tab-root_slugs-locale-en-panel"', $html);
        self::assertStringContainsString('aria-labelledby="tab-root_slugs-locale-en"', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-es"[^>]*aria-selected="true"/s', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en"[^>]*aria-selected="false"/s', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en-panel"[^>]*\shidden/s', $html);
        self::assertStringContainsString('data-controller="tabs" data-tabs-active-tab-value="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-action="click->tabs#open" data-tabs-target="trigger" data-tab-id="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-nowo-ui-tabs data-nowo-ui-tabs-active="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-nowo-ui-tabs-panel data-nowo-ui-tab-id="root_slugs-locale-en"', $html);
        self::assertStringContainsString('— Spanish', $html);
        self::assertStringContainsString('nowo-form-kit-locale-tabs__default', $html);
        self::assertStringContainsString('name="root[slugs][es]"', $html);
        self::assertStringContainsString('name="root[slugs][en]"', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
    }

    public function testThemeRespectsLocaleOrderLabelsAndStimulusToggles(): void
    {
        $form = $this->slugsForm(['label' => 'URL', 'help' => 'One per language', 'locale_tabs' => [
            'locales'             => ['en', 'es'],
            'default_locale'      => 'es',
            'active_locale'       => 'en',
            'label'               => 'Slug (%locale%)',
            'help_default'        => 'Main %locale%',
            'help_other'          => 'Other %locale%',
            'tabs_label'          => 'Languages',
            'stimulus_controller' => false,
            'kit_attributes'      => false,
            'mark_default'        => false,
            'testid'              => 'slugs-tabs',
            'tabs_id'             => 'slug',
        ]]);

        $html = $this->render("{% form_theme form '@NowoFormKitBundle/form/locale_tabs_theme.html.twig' %}{{ form_row(form.slugs) }}", $form);

        self::assertStringContainsString('data-testid="slugs-tabs"', $html);
        self::assertStringContainsString('>URL</p>', $html);
        self::assertStringContainsString('One per language', $html);
        self::assertStringContainsString('aria-label="Languages"', $html);
        self::assertLessThan(strpos($html, 'id="tab-slug-es"'), strpos($html, 'id="tab-slug-en"'));
        self::assertMatchesRegularExpression('/id="tab-slug-en"[^>]*aria-selected="true"/s', $html);
        self::assertStringContainsString('Slug (EN)', $html);
        self::assertStringContainsString('Main es', $html);
        self::assertStringContainsString('Other en', $html);
        self::assertStringNotContainsString('data-controller', $html);
        self::assertStringNotContainsString('data-nowo-ui-tabs', $html);
        self::assertStringNotContainsString('__default', $html);
    }

    public function testInvalidLocaleTabIsMarkedAndOpened(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['default_locale' => 'es', 'active_locale' => 'es']]);
        $form->submit(['slugs' => ['es' => 'hola', 'en' => '']]);
        $form->get('slugs')->get('en')->addError(new FormError('Required.'));

        $html = $this->render("{% form_theme form '@NowoFormKitBundle/form/locale_tabs_theme.html.twig' %}{{ form_row(form.slugs) }}", $form);

        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en"[^>]*aria-selected="true"[^>]*data-invalid/s', $html);
        self::assertStringContainsString('nowo-form-kit-locale-tabs__error', $html);
        self::assertStringContainsString('Required.', $html);
    }

    public function testPartialIncludeWithDefaultChildAndRequestLocale(): void
    {
        $factory = $this->factory();
        $form    = $factory->createNamedBuilder('root', FormType::class)
            ->add('slug', TextType::class)
            ->add('slugs', FormType::class)
            ->getForm();
        $form->get('slugs')->add('es', TextType::class)->add('en', TextType::class);

        $html = $this->render(
            "{% include '@NowoFormKitBundle/form/_locale_tabs.html.twig' with {field: form.slugs, default_child: form.slug, locales: ['es', 'en', 'fr'], title: 'Slug', activate_invalid: false} only %}{{ form_rest(form) }}",
            $form,
            ['app' => ['request' => ['locale' => 'en']]],
        );

        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en"[^>]*aria-selected="true"/s', $html);
        self::assertStringContainsString('name="root[slug]"', $html);
        self::assertStringNotContainsString('name="root[slugs][es]"', $html, 'default_child replaces field[default_locale]');
        self::assertStringContainsString('id="tab-root_slugs-locale-fr-panel"', $html);
        // field marked rendered → form_rest() must not render the children again
        self::assertSame(1, substr_count($html, 'name="root[slugs][en]"'));
    }

    public function testUnknownRequestLocaleFallsBackToDefault(): void
    {
        $form = $this->slugsForm(['locale_tabs' => true]);

        $html = $this->render("{% form_theme form '@NowoFormKitBundle/form/locale_tabs_theme.html.twig' %}{{ form_row(form.slugs) }}", $form, ['app' => ['request' => ['locale' => 'de']]]);

        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-es"[^>]*aria-selected="true"/s', $html);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return FormInterface<mixed>
     */
    private function slugsForm(array $options): FormInterface
    {
        $form = $this->factory()->createNamedBuilder('root', FormType::class)
            ->add('slugs', FormType::class, $options)
            ->getForm();
        $form->get('slugs')->add('es', TextType::class, ['required' => false])->add('en', TextType::class, ['required' => false]);

        return $form;
    }

    private function factory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeExtension(new LocaleTabsExtension())->getFormFactory();
    }

    /**
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $globals
     */
    private function render(string $template, FormInterface $form, array $globals = []): string
    {
        $root = dirname(__DIR__, 3);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($root . '/src/Resources/views', 'NowoFormKitBundle');

        $twig = new Environment(new ChainLoader([new ArrayLoader(['t.twig' => $template]), $fs]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new TranslationExtension());
        $twig->addExtension(new FormExtension());
        foreach ($globals as $name => $value) {
            $twig->addGlobal($name, $value);
        }

        $engine = new TwigRendererEngine(['form_div_layout.html.twig'], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine),
        ]));

        return $twig->render('t.twig', ['form' => $form->createView()]);
    }
}
