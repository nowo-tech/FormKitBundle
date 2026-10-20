<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension;
use Nowo\UiKitBundle\Theme\Theme;
use Nowo\UiKitBundle\Twig\ThemeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function basename;
use function dirname;
use function explode;
use function glob;
use function strpos;
use function substr_count;

final class LocaleTabsThemeTest extends TestCase
{
    private const THEME = "{% form_theme form '@NowoFormKitBundle/form/locale_tabs_theme.html.twig' %}";

    public function testNeutralRendererRendersOneAccessibleTabPerLocale(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['default_locale' => 'es', 'locale_labels' => ['es' => 'Spanish', 'en' => 'English']]]);

        $html = $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form);

        self::assertStringContainsString('role="tablist" aria-label="Slugs"', $html);
        self::assertSame(2, substr_count($html, 'role="tab"'));
        self::assertSame(2, substr_count($html, 'role="tabpanel"'));
        self::assertStringContainsString('id="tab-root_slugs-locale-es"', $html);
        self::assertStringContainsString('aria-controls="tab-root_slugs-locale-en-panel"', $html);
        self::assertStringContainsString('aria-labelledby="tab-root_slugs-locale-en"', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-es"[^>]*aria-selected="true"[^>]*tabindex="0"/s', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en"[^>]*aria-selected="false"[^>]*tabindex="-1"/s', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en-panel"[^>]*lang="en"[^>]*dir="ltr"[^>]*\shidden/s', $html);
        self::assertStringContainsString('data-controller="tabs" data-tabs-active-tab-value="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-action="click->tabs#open" data-tabs-target="trigger" data-tab-id="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-nowo-ui-tabs data-nowo-ui-tabs-active="root_slugs-locale-es"', $html);
        self::assertStringContainsString('data-nowo-ui-tabs-panel data-nowo-ui-tab-id="root_slugs-locale-en"', $html);
        self::assertStringContainsString('(Spanish, locale_tabs.default)', $html);
        self::assertStringContainsString('<span class="nowo-form-kit-locale-tabs__default" aria-hidden="true">★</span>', $html);
        self::assertStringContainsString('name="root[slugs][es]"', $html);
        self::assertStringContainsString('name="root[slugs][en]"', $html);
        self::assertStringNotContainsString('data-nowo-ui-locale-tabs', $html, 'no UiKit: neutral markup');
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
    }

    public function testNeutralRendererOptions(): void
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
            'required_locales'    => ['en'],
            'required_marker'     => 'R',
        ]]);

        $html = $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form);

        self::assertStringContainsString('data-testid="slugs-tabs"', $html);
        self::assertStringContainsString('>URL</p>', $html);
        self::assertStringContainsString('One per language', $html);
        self::assertStringContainsString('aria-label="Languages"', $html);
        // The default locale is listed first.
        self::assertLessThan(strpos($html, 'id="tab-slug-en"'), strpos($html, 'id="tab-slug-es"'));
        self::assertMatchesRegularExpression('/id="tab-slug-en"[^>]*aria-selected="true"[^>]*data-required/s', $html);
        self::assertStringContainsString('<span class="nowo-form-kit-locale-tabs__required" aria-hidden="true">R</span>', $html);
        self::assertStringContainsString('Slug (EN)', $html);
        self::assertStringContainsString('Main es', $html);
        self::assertStringContainsString('Other en', $html);
        self::assertStringNotContainsString('data-controller', $html);
        self::assertStringNotContainsString('data-nowo-ui-tabs', $html);
        self::assertStringNotContainsString('__default', $html);
    }

    public function testRowVariablesAreMergedOverTheFormTypeOption(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['default_locale' => 'es', 'tabs_id' => 'slug', 'testid' => 'from-type']]);

        $html = $this->render(self::THEME . "{{ form_row(form.slugs, {locale_tabs: {active_locale: 'en', testid: 'from-row', tabs_label: 'Idiomas'}}) }}", $form);

        // Row variables win, the form-type keys they do not set stay.
        self::assertStringContainsString('data-testid="from-row"', $html);
        self::assertStringNotContainsString('from-type', $html);
        self::assertStringContainsString('aria-label="Idiomas"', $html);
        self::assertMatchesRegularExpression('/id="tab-slug-en"[^>]*aria-selected="true"/s', $html);
        self::assertLessThan(strpos($html, 'id="tab-slug-en"'), strpos($html, 'id="tab-slug-es"'), 'default_locale from the type');

        // Without row variables: the form-type option only.
        $html = $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form);
        self::assertStringContainsString('data-testid="from-type"', $html);
        self::assertMatchesRegularExpression('/id="tab-slug-es"[^>]*aria-selected="true"/s', $html);
    }

    public function testInvalidLocaleTabIsMarkedOpenedAndItsWidgetAriaInvalid(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['default_locale' => 'es', 'active_locale' => 'es', 'error_marker' => 'err']]);
        $form->submit(['slugs' => ['es' => 'hola', 'en' => '']]);
        $form->get('slugs')->get('en')->addError(new FormError('Required.'));

        $html = $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form);

        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en"[^>]*aria-selected="true"[^>]*data-invalid/s', $html);
        self::assertStringContainsString('<span class="err" aria-hidden="true">!</span>', $html);
        self::assertStringContainsString('(locale_tabs.invalid)', $html);
        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-en-panel"[^>]*data-invalid/s', $html);
        self::assertMatchesRegularExpression('/<input[^>]*name="root\[slugs\]\[en\]"[^>]*aria-invalid="true"/s', $html);
        self::assertDoesNotMatchRegularExpression('/<input[^>]*name="root\[slugs\]\[es\]"[^>]*aria-invalid/s', $html);
        self::assertStringContainsString('Required.', $html);
    }

    public function testPartialIncludeWithDefaultChildAndRequestLocale(): void
    {
        $form = $this->factory()->createNamedBuilder('root', FormType::class)
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

        $html = $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form, ['app' => ['request' => ['locale' => 'de']]]);

        self::assertMatchesRegularExpression('/id="tab-root_slugs-locale-es"[^>]*aria-selected="true"/s', $html);
    }

    public function testUiKitRendererForChildrenWithRequiredAndInvalidLocales(): void
    {
        $form = $this->slugsForm(['label' => 'Slug', 'help' => 'One per language', 'locale_tabs' => ['default_locale' => 'en', 'required_locales' => ['en'], 'locale_labels' => ['en' => 'English', 'es' => 'Español']]]);
        $form->submit(['slugs' => ['es' => '', 'en' => 'hello']]);
        $form->get('slugs')->get('es')->addError(new FormError('Fill me.'));

        $c = new Crawler($this->render(self::THEME . '{{ form_row(form.slugs) }}', $form, uiKit: true));

        $wrapper = $c->filter('.nowo-form-kit-locale-tabs');
        self::assertSame('Slug', $wrapper->filter('.nowo-form-kit-locale-tabs__title')->text());
        self::assertSame('One per language', $wrapper->filter('p.nowo-form-kit-locale-tabs__help.nowo-ui-muted')->text(), 'UiKit muted slot on the help');
        $root = $wrapper->filter('#root_slugs-locale[data-nowo-ui-locale-tabs][data-nowo-ui-tabs-upgrade]');
        self::assertCount(1, $root);
        self::assertSame('root_slugs-locale-es', $root->attr('data-nowo-ui-tabs-active'), 'the invalid locale opens');
        self::assertSame('tabs', $root->attr('data-controller'));
        self::assertSame('Slug', $c->filter('[role="tablist"]')->attr('aria-label'));
        self::assertSame(['en', 'es'], $c->filter('[role="tab"]')->each(static fn (Crawler $t): string => (string) $t->attr('data-locale')));
        self::assertNotNull($c->filter('#tab-root_slugs-locale-en')->attr('data-required'));
        self::assertNotNull($c->filter('#tab-root_slugs-locale-es')->attr('data-invalid'));
        self::assertSame('(Español, locale_tabs.invalid)', $c->filter('#tab-root_slugs-locale-es .visually-hidden')->text());
        self::assertSame('true', $c->filter('#tab-root_slugs-locale-es-panel input[name="root[slugs][es]"]')->attr('aria-invalid'));
        self::assertSame('Fill me.', trim($c->filter('#tab-root_slugs-locale-es-panel ul li')->text()));
        self::assertCount(1, $c->filter('#tab-root_slugs-locale-en-panel fieldset > legend'));
    }

    public function testUiKitRendererCanBeDisabledPerFieldAndByConfig(): void
    {
        $form = $this->slugsForm(['locale_tabs' => ['ui_kit' => false]]);
        self::assertStringNotContainsString('data-nowo-ui-locale-tabs', $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form, uiKit: true));

        $form = $this->slugsForm(['locale_tabs' => true]);
        self::assertStringNotContainsString('data-nowo-ui-locale-tabs', $this->render(self::THEME . '{{ form_row(form.slugs) }}', $form, uiKit: true, extension: new LocaleTabsTwigExtension(uiKit: false)));

        $static = new Crawler($this->render(self::THEME . '{{ form_row(form.slugs) }}', $this->slugsForm(['locale_tabs' => ['progressive' => false, 'stimulus_controller' => false]]), uiKit: true));
        self::assertNull($static->filter('[data-nowo-ui-locale-tabs]')->attr('data-nowo-ui-tabs-upgrade'));
        self::assertNull($static->filter('[data-nowo-ui-locale-tabs]')->attr('data-controller'));
    }

    public function testEntriesModeListOfTranslationEntities(): void
    {
        foreach ([false, true] as $uiKit) {
            $form = $this->factory()->createNamedBuilder('article', FormType::class, ['translations' => [
                ['locale' => 'en', 'title' => 'Hello', 'body' => 'World'],
                ['locale' => 'es', 'title' => '', 'body' => 'Mundo'],
            ]])->add('translations', CollectionType::class, ['entry_type' => DemoTranslationType::class, 'entry_options' => ['error_bubbling' => false], 'locale_tabs' => ['default_locale' => 'es']])->getForm();
            $form->submit(['translations' => [['locale' => 'en', 'title' => 'Hello', 'body' => 'World'], ['locale' => 'es', 'title' => '', 'body' => 'Mundo']]]);
            $form->get('translations')->get('1')->get('title')->addError(new FormError('Title missing.'));
            $form->get('translations')->get('1')->addError(new FormError('Entry error.'));

            $html = $this->render(self::THEME . '{{ form_row(form.translations) }}{{ form_rest(form) }}', $form, uiKit: $uiKit);
            $c    = new Crawler($html);

            self::assertSame(['es', 'en'], $c->filter('[role="tab"]')->each(static fn (Crawler $t): string => (string) $t->attr('data-locale')), 'uiKit=' . ($uiKit ? 1 : 0));
            $es = $c->filter('#tab-article_translations-locale-es-panel');
            self::assertSame('es', $es->filter('input[type="hidden"][name="article[translations][1][locale]"]')->attr('value'));
            self::assertSame('article_translations_1_locale', $es->filter('input[type="hidden"]')->attr('id'));
            self::assertCount(1, $es->filter('input[name="article[translations][1][title]"][aria-invalid="true"]'));
            self::assertCount(1, $es->filter('input[name="article[translations][1][body]"]'));
            self::assertStringContainsString('Entry error.', $es->text(), 'entry errors (error_bubbling: false) in the panel');
            self::assertNotNull($c->filter('#tab-article_translations-locale-es')->attr('data-invalid'));
            self::assertNull($c->filter('#tab-article_translations-locale-en')->attr('data-invalid'));
            self::assertSame(1, substr_count($html, 'name="article[translations][0][locale]"'), 'form_rest does not render the entries again');
            self::assertCount(0, $c->filter('input[type="text"][name$="[locale]"]'), 'the locale child is never a visible input');
        }
    }

    public function testFieldsModeGroupsFlatFieldsAndLeavesTheRestToFormRest(): void
    {
        $form = $this->factory()->createNamedBuilder('perm', FormType::class)
            ->add('code', TextType::class)
            ->add('name_es', TextType::class, ['required' => true])
            ->add('name_en', TextType::class, ['required' => false])
            ->add('description_es', TextType::class, ['required' => false])
            ->add('description_en', TextType::class, ['required' => false])
            ->getForm();
        $form->submit(['code' => 'x', 'name_es' => '', 'name_en' => 'Name', 'description_es' => '', 'description_en' => '']);
        $form->get('name_es')->addError(new FormError('Name missing.'));

        $html = $this->render(
            "{% include '@NowoFormKitBundle/form/_locale_tabs.html.twig' with {field: form, fields: ['name', 'description'], required_locales: ['es'], tabs_id: 'perm-i18n'} only %}{{ form_rest(form) }}",
            $form,
            uiKit: true,
            extension: new LocaleTabsTwigExtension(['en', 'es'], 'es'),
        );
        $c = new Crawler($html);

        self::assertSame(['es', 'en'], $c->filter('[role="tab"]')->each(static fn (Crawler $t): string => (string) $t->attr('data-locale')), 'configured locales, configured default first');
        self::assertSame(['perm[name_es]', 'perm[description_es]'], $c->filter('#tab-perm-i18n-es-panel input')->each(static fn (Crawler $i): string => (string) $i->attr('name')));
        self::assertSame(['perm[name_en]', 'perm[description_en]'], $c->filter('#tab-perm-i18n-en-panel input')->each(static fn (Crawler $i): string => (string) $i->attr('name')));
        self::assertSame('perm-i18n-es', $c->filter('#perm-i18n')->attr('data-nowo-ui-tabs-active'));
        self::assertNotNull($c->filter('#tab-perm-i18n-es')->attr('data-required'));
        self::assertNotNull($c->filter('#tab-perm-i18n-es')->attr('data-invalid'));
        // Other fields stay for form_rest(), the grouped ones are rendered once.
        self::assertSame(1, substr_count($html, 'name="perm[code]"'));
        self::assertSame(1, substr_count($html, 'name="perm[name_en]"'));
        self::assertGreaterThan(strpos($html, 'perm-i18n-en-panel'), strpos($html, 'name="perm[code]"'));

        // Custom pattern, explicit locales; a locale without fields gets no tab.
        $form = $this->factory()->createNamedBuilder('seo', FormType::class)
            ->add('es_title', TextType::class)->add('en_title', TextType::class)->getForm();
        $c = new Crawler($this->render("{% include '@NowoFormKitBundle/form/_locale_tabs.html.twig' with {field: form, fields: ['title'], pattern: '{locale}_{field}', locales: ['es', 'en', 'fr']} only %}", $form));
        self::assertSame(['es', 'en'], $c->filter('[role="tab"]')->each(static fn (Crawler $t): string => (string) $t->attr('data-locale')));
    }

    public function testTranslatableMapTypeThroughUiKitWithTranslatedLabels(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addTypeExtension(new LocaleTabsExtension())
            ->addType(new TranslatableMapType(['es', 'en', 'ar'], 'en'))
            ->getFormFactory();
        $form = $factory->createNamedBuilder('item', FormType::class, ['labels' => ['es' => 'Hola', 'xx' => 'kept']])
            ->add('labels', TranslatableMapType::class, ['required_locales' => ['en'], 'label' => 'Label'])
            ->getForm();
        $form->submit(['labels' => ['es' => 'Hola', 'en' => '', 'ar' => 'مرحبا']]);

        $translator = new Translator('es');
        $translator->addLoader('yaml', new YamlFileLoader());
        foreach ([dirname(__DIR__, 3) . '/src/Resources/translations', dirname(__DIR__, 3) . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/translations'] as $dir) {
            foreach (glob($dir . '/*.yaml') ?: [] as $file) {
                $parts = explode('.', basename($file));
                $translator->addResource('yaml', $file, $parts[1], $parts[0]);
            }
        }

        $c = new Crawler($this->render(self::THEME . '{{ form_row(form.labels) }}', $form, uiKit: true, translator: $translator));

        self::assertFalse($form->isValid());
        self::assertSame(['en', 'es', 'ar'], $c->filter('[role="tab"]')->each(static fn (Crawler $t): string => (string) $t->attr('data-locale')), 'default (en) first');
        self::assertSame('item_labels-locale-en', $c->filter('#item_labels-locale')->attr('data-nowo-ui-tabs-active'), 'the invalid required locale opens');
        self::assertNotNull($c->filter('#tab-item_labels-locale-en')->attr('data-required'));
        self::assertNotNull($c->filter('#tab-item_labels-locale-en')->attr('data-invalid'));
        self::assertNotNull($c->filter('input[name="item[labels][en]"][required]')->getNode(0));
        self::assertNull($c->filter('input[name="item[labels][es]"]')->attr('required'));
        self::assertSame('rtl', $c->filter('#tab-item_labels-locale-ar-panel')->attr('dir'));
        self::assertSame('Label', $c->filter('[role="tablist"]')->attr('aria-label'));
        self::assertStringContainsString('obligatorio', $c->filter('#tab-item_labels-locale-en .visually-hidden')->text());

        // Neutral renderer: the translated tab list label.
        $neutral = new Crawler($this->render(self::THEME . '{{ form_row(form.slugs) }}', $this->slugsForm(['locale_tabs' => ['title' => '']]), translator: $translator));
        self::assertSame('Idiomas', $neutral->filter('[role="tablist"]')->attr('aria-label'));
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
    private function render(string $template, FormInterface $form, array $globals = [], bool $uiKit = false, ?LocaleTabsTwigExtension $extension = null, ?Translator $translator = null): string
    {
        $root = dirname(__DIR__, 3);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($root . '/src/Resources/views', 'NowoFormKitBundle');
        if ($uiKit) {
            $fs->addPath($root . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/views', 'NowoUiKitBundle');
        }

        $twig = new Environment(new ChainLoader([new ArrayLoader(['t.twig' => $template]), $fs]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addExtension(new FormExtension());
        $twig->addExtension($extension ?? new LocaleTabsTwigExtension());
        if ($uiKit) {
            $twig->addExtension(new ThemeExtension(new Theme(), translator: $translator));
        }
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

/**
 * An entry of a list of translation entities: a locale plus the translated fields.
 *
 * @extends AbstractType<array<string, string>>
 */
final class DemoTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('locale', HiddenType::class)->add('title', TextType::class)->add('body', TextType::class);
    }
}
