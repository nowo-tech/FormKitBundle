<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Extension\CharCounterExtension;
use Nowo\FormKitBundle\Form\Extension\CheckboxRowExtension;
use Nowo\FormKitBundle\Form\Extension\ChoiceCardsExtension;
use Nowo\FormKitBundle\Form\Extension\SwitchExtension;
use Nowo\UiKitBundle\Theme\Theme;
use Nowo\UiKitBundle\Twig\ThemeExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Length;
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
use function preg_match_all;
use function substr_count;

/**
 * 2.22: `@NowoFormKitBundle/form/field_widgets_theme.html.twig` (choice cards, switch, character counter) on every
 * supported base theme, and byte-identical rendering of fields that do not use the options.
 */
final class FieldWidgetsThemeTest extends TestCase
{
    private const UI_KIT_LAYOUT = '@NowoUiKit/form/nowo_ui_layout.html.twig';

    /**
     * @return iterable<string, array{string}>
     */
    public static function baseThemes(): iterable
    {
        yield 'form_div_layout' => ['form_div_layout.html.twig'];
        yield 'bootstrap_5_layout' => ['bootstrap_5_layout.html.twig'];
        yield 'bootstrap_5_horizontal_layout' => ['bootstrap_5_horizontal_layout.html.twig'];
        yield 'tailwind_2_layout' => ['tailwind_2_layout.html.twig'];
        yield 'ui_kit nowo_ui_layout' => [self::UI_KIT_LAYOUT];
    }

    #[DataProvider('baseThemes')]
    public function testFieldsWithoutTheOptionsRenderByteIdentical(string $baseTheme): void
    {
        $before = $this->render($this->fixtureForm($this->factory(false)), $baseTheme, false);
        $after  = $this->render($this->fixtureForm($this->factory(true)), $baseTheme, true);

        self::assertSame($before, $after);
        self::assertStringNotContainsString('nowo-form-kit-', $after);
    }

    #[DataProvider('baseThemes')]
    public function testChoiceCardsRenderOnEveryBaseTheme(string $baseTheme): void
    {
        $form = $this->factory()->createNamedBuilder('order', FormType::class, ['plan' => 'pro'])
            ->add('plan', ChoiceType::class, [
                'label'                         => 'Plan for %who%',
                'label_translation_parameters'  => ['%who%' => 'Ana'],
                'expanded'                      => true,
                'choices'                       => ['Basic %tier%' => 'basic', 'Pro %tier%' => 'pro', 'Team' => 'team'],
                'choice_translation_parameters' => static fn (): array => ['%tier%' => 'tier'],
                'choice_attr'                   => static fn (string $choice): array => $choice === 'team' ? ['disabled' => true, 'data-x' => 'y', 'title' => 'Soon'] : [],
                'choice_cards'                  => [
                    'description' => ['basic' => 'For one person', 'pro' => '<b>For teams</b>'],
                    'icon_html'   => static fn (string $choice, string $key, string $value): ?string => $value === 'basic' ? '<svg class="i"></svg>' : null,
                    'image'       => static fn (string $choice, string $key, string $value): ?array => $value === 'pro' ? ['src' => '/img/pro.svg', 'alt' => 'Pro badge'] : null,
                    'columns'     => 3,
                ],
            ])
            ->getForm();

        $html = $this->render($form, $baseTheme);
        $c    = new Crawler($html);

        $group = $c->filter('#order_plan');
        self::assertSame('radiogroup', $group->attr('role'));
        self::assertSame('order_plan_label', $group->attr('aria-labelledby'));
        self::assertStringContainsString('nowo-form-kit-choice-cards nowo-form-kit-choice-cards--cols-3', (string) $group->attr('class'));
        self::assertCount(1, $c->filter('#order_plan_label'), 'the base theme prints label_attr.id on the group label');
        self::assertStringContainsString('Plan for Ana', $c->filter('#order_plan_label')->text());

        self::assertCount(3, $c->filter('.nowo-form-kit-choice-card'));
        self::assertCount(3, $c->filter('input[type="radio"].nowo-form-kit-choice-card__input'));
        foreach (['basic' => 0, 'pro' => 1, 'team' => 2] as $value => $i) {
            self::assertCount(1, $c->filter('label[for="order_plan_' . $i . '"]'), $value);
            self::assertSame($value, $c->filter('#order_plan_' . $i)->attr('value'));
            self::assertSame('order[plan]', $c->filter('#order_plan_' . $i)->attr('name'));
        }
        self::assertSame('Basic tier', $c->filter('label[for="order_plan_0"]')->text());
        self::assertSame('checked', $c->filter('#order_plan_1')->attr('checked'));
        self::assertNull($c->filter('#order_plan_0')->attr('checked'));
        self::assertSame('order_plan_0_description', $c->filter('#order_plan_0')->attr('aria-describedby'));
        self::assertSame('For one person', $c->filter('#order_plan_0_description')->text());
        self::assertStringContainsString('&lt;b&gt;For teams&lt;/b&gt;', $html, 'descriptions are escaped');
        self::assertNull($c->filter('#order_plan_2')->attr('aria-describedby'));
        self::assertSame('<svg class="i"></svg>', $c->filter('.nowo-form-kit-choice-card__icon')->html(), 'icon_html is trusted HTML');
        self::assertSame('true', $c->filter('.nowo-form-kit-choice-card__icon')->attr('aria-hidden'));
        self::assertSame('Pro badge', $c->filter('img.nowo-form-kit-choice-card__image')->attr('alt'));
        self::assertSame('/img/pro.svg', $c->filter('img.nowo-form-kit-choice-card__image')->attr('src'));
        self::assertSame('disabled', $c->filter('#order_plan_2')->attr('disabled'));
        self::assertSame('y', $c->filter('#order_plan_2')->attr('data-x'));
        self::assertSame('Soon', $c->filter('#order_plan_2')->attr('title'));
        self::assertStringContainsString('nowo-form-kit-choice-card--disabled', (string) $c->filter('.nowo-form-kit-choice-card')->eq(2)->attr('class'));
        self::assertSame('true', $group->attr('aria-required'));
        $this->assertCspSafe($html);
    }

    public function testMultipleChoiceCardsAreCheckboxesInAGroupAndShowErrors(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class)
            ->add('tags', ChoiceType::class, [
                'expanded'                  => true,
                'multiple'                  => true,
                'label'                     => false,
                'choices'                   => ['A' => 'a', 'B' => 'b'],
                'choice_cards'              => true,
                'choice_translation_domain' => false,
                'attr'                      => ['class' => 'mine'],
            ])
            ->getForm();
        $form->get('tags')->addError(new FormError('Pick one'));

        $c     = new Crawler($this->render($form, 'form_div_layout.html.twig'));
        $group = $c->filter('#f_tags');
        self::assertSame('group', $group->attr('role'));
        self::assertNull($group->attr('aria-labelledby'), 'label: false prints no label to point at');
        self::assertNull($group->attr('aria-required'));
        self::assertSame('mine nowo-form-kit-choice-cards nowo-form-kit-choice-cards--invalid', $group->attr('class'));
        self::assertCount(2, $c->filter('input[type="checkbox"][aria-invalid="true"]'));
        self::assertSame('f[tags][]', $c->filter('#f_tags_0')->attr('name'));
        self::assertCount(0, $c->filter('.nowo-form-kit-choice-card__description, .nowo-form-kit-choice-card__image'));
    }

    public function testChoiceCardsKeepAnExplicitAccessibleNameAndLabelId(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class)
            ->add('a', ChoiceType::class, ['expanded' => true, 'choices' => ['X' => 'x'], 'choice_cards' => true, 'label_attr' => ['id' => 'custom'], 'required' => false, 'placeholder' => 'None'])
            ->add('b', ChoiceType::class, ['expanded' => true, 'choices' => ['X' => 'x'], 'choice_cards' => true, 'attr' => ['aria-label' => 'Own']])
            ->getForm();

        $c = new Crawler($this->render($form, 'bootstrap_5_layout.html.twig'));
        self::assertSame('custom', $c->filter('#f_a')->attr('aria-labelledby'));
        self::assertCount(1, $c->filter('#custom'));
        self::assertCount(2, $c->filter('#f_a input[type="radio"]'), 'the placeholder choice is a card too');
        self::assertSame('', $c->filter('#f_a_placeholder')->attr('value'));
        self::assertSame('Own', $c->filter('#f_b')->attr('aria-label'));
        self::assertNull($c->filter('#f_b')->attr('aria-labelledby'));
    }

    #[DataProvider('baseThemes')]
    public function testSwitchRendersTheSameControlOnEveryBaseTheme(string $baseTheme): void
    {
        $form = $this->factory()->createNamedBuilder('s', FormType::class, ['dark' => true])
            ->add('dark', CheckboxType::class, ['label' => 'Dark %what%', 'label_translation_parameters' => ['%what%' => 'mode'], 'help' => 'Follows the system', 'switch' => ['size' => 'lg', 'icons' => true]])
            ->add('news', CheckboxType::class, ['switch' => true, 'required' => false, 'row_attr' => ['class' => 'mine', 'data-r' => '1']])
            ->getForm();

        $html = $this->render($form, $baseTheme);
        $c    = new Crawler($html);

        $dark = $c->filter('#s_dark');
        self::assertSame('switch', $dark->attr('role'));
        self::assertSame('checkbox', $dark->attr('type'));
        self::assertSame('checked', $dark->attr('checked'));
        self::assertSame('1', $dark->attr('value'));
        self::assertSame('s_dark_help', $dark->attr('aria-describedby'));
        self::assertSame('nowo-form-kit-switch__input', $dark->attr('class'));
        self::assertSame(1, preg_match_all('/<label[^>]*for="s_dark"/', $html), 'one label per switch');
        self::assertSame('Dark mode', $c->filter('label[for="s_dark"] .nowo-form-kit-switch__text')->text());
        self::assertCount(2, $c->filter('label[for="s_dark"] .nowo-form-kit-switch__icon'));
        self::assertSame('true', $c->filter('label[for="s_dark"] .nowo-form-kit-switch__track')->attr('aria-hidden'));
        self::assertCount(1, $c->filter('#s_dark_help'));
        $row = $dark->ancestors()->first();
        self::assertStringContainsString('nowo-form-kit-switch nowo-form-kit-switch--lg nowo-form-kit-switch--icons', (string) $row->attr('class'));
        self::assertSame('mb-3 nowo-form-kit-switch nowo-form-kit-switch--lg nowo-form-kit-switch--icons', $row->attr('class'), 'row class of the Bootstrap profile (CheckboxRowExtension)');
        self::assertStringNotContainsString('form-check', $html, 'framework-neutral markup');

        $news = $c->filter('#s_news');
        self::assertNull($news->attr('checked'));
        self::assertNull($news->attr('required'));
        self::assertNull($news->attr('aria-describedby'));
        self::assertSame('mine nowo-form-kit-switch nowo-form-kit-switch--md', $news->ancestors()->first()->attr('class'));
        self::assertSame('1', $news->ancestors()->first()->attr('data-r'));
        self::assertCount(0, $c->filter('label[for="s_news"] .nowo-form-kit-switch__icon'));
        $this->assertCspSafe($html);
    }

    public function testSwitchWithoutLabelErrorsAndDisabled(): void
    {
        $form = $this->factory()->createNamedBuilder('s', FormType::class)
            ->add('on', CheckboxType::class, ['switch' => ['size' => 'sm'], 'label' => false, 'disabled' => true, 'attr' => ['aria-label' => 'On']])
            ->getForm();
        $form->get('on')->addError(new FormError('Nope'));

        $c = new Crawler($this->render($form, 'form_div_layout.html.twig'));
        self::assertCount(0, $c->filter('.nowo-form-kit-switch__text'));
        self::assertSame('On', $c->filter('#s_on')->attr('aria-label'));
        self::assertSame('true', $c->filter('#s_on')->attr('aria-invalid'));
        self::assertSame('disabled', $c->filter('#s_on')->attr('disabled'));
        self::assertStringContainsString('Nope', $c->filter('.nowo-form-kit-switch--sm')->text());
    }

    #[DataProvider('baseThemes')]
    public function testCharCounterRendersTheLimitOnEveryBaseTheme(string $baseTheme): void
    {
        $form = $this->factory()->createNamedBuilder('p', FormType::class)
            ->add('title', TextType::class, ['help' => 'Shown in search results', 'char_counter' => true, 'constraints' => [new Length(max: 60)]])
            ->add('bio', TextareaType::class, ['char_counter' => ['max' => 280, 'min' => 10, 'threshold' => 20, 'unit' => 'graphemes']])
            ->getForm();

        $html = $this->render($form, $baseTheme);
        $c    = new Crawler($html);

        $title = $c->filter('#p_title');
        self::assertSame('p_title_help p_title_counter', $title->attr('aria-describedby'));
        self::assertSame('p_title_counter', $title->attr('data-nowo-form-kit-counter'));
        self::assertNull($title->attr('maxlength'), 'the counter never truncates input');
        $counter = $c->filter('#p_title_counter');
        self::assertSame('Maximum 60 characters.', $counter->filter('.nowo-form-kit-counter__limit')->text());
        self::assertSame('60', $counter->attr('data-nowo-form-kit-counter-max'));
        self::assertSame('6', $counter->attr('data-nowo-form-kit-counter-threshold'));
        self::assertNull($counter->attr('data-nowo-form-kit-counter-min'));
        self::assertSame('codepoints', $counter->attr('data-nowo-form-kit-counter-unit'), 'the unit of the Length constraint');
        self::assertSame('%count% / %max%', $counter->attr('data-nowo-form-kit-counter-text-max'));
        self::assertSame('Characters left: %count%', $counter->attr('data-nowo-form-kit-counter-text-remaining'));
        self::assertSame('Characters over the limit: %count%', $counter->attr('data-nowo-form-kit-counter-text-over'));
        self::assertSame('true', $counter->filter('.nowo-form-kit-counter__count')->attr('aria-hidden'));
        self::assertSame('polite', $c->filter('#p_title_counter_live')->attr('aria-live'));
        self::assertCount(1, $c->filter('#p_title_help'));

        $bio = $c->filter('textarea#p_bio');
        self::assertSame('p_bio_counter', $bio->attr('aria-describedby'));
        self::assertSame('Between 10 and 280 characters.', $c->filter('#p_bio_counter .nowo-form-kit-counter__limit')->text());
        self::assertSame('20', $c->filter('#p_bio_counter')->attr('data-nowo-form-kit-counter-threshold'));
        self::assertSame('10', $c->filter('#p_bio_counter')->attr('data-nowo-form-kit-counter-min'));
        self::assertSame('graphemes', $c->filter('#p_bio_counter')->attr('data-nowo-form-kit-counter-unit'));
        $this->assertCspSafe($html);
    }

    public function testCharCounterLimitSources(): void
    {
        $form = $this->factory()->createNamedBuilder('p', FormType::class, new FieldWidgetsArticle(), ['data_class' => FieldWidgetsArticle::class])
            ->add('summary', TextType::class, ['char_counter' => true])
            ->add('slug', TextType::class, ['char_counter' => true, 'attr' => ['maxlength' => '40', 'minlength' => 3]])
            ->add('email', EmailType::class, ['char_counter' => true, 'mapped' => false, 'attr' => ['maxlength' => 'x']])
            ->add('note', TextType::class, ['char_counter' => ['min' => 5], 'mapped' => false])
            ->getForm();

        $c = new Crawler($this->render($form, 'form_div_layout.html.twig', translator: $this->translator('es')));

        self::assertSame('Entre 5 y 120 caracteres.', $c->filter('#p_summary_counter')->text(), 'Length on the mapped property (validator metadata), translated');
        self::assertSame('bytes', $c->filter('#p_summary_counter')->attr('data-nowo-form-kit-counter-unit'));
        self::assertSame('Entre 3 y 40 caracteres.', $c->filter('#p_slug_counter')->text(), 'maxlength / minlength attributes');
        self::assertSame('graphemes', $c->filter('#p_slug_counter')->attr('data-nowo-form-kit-counter-unit'));
        self::assertSame('40', $c->filter('#p_slug')->attr('maxlength'), 'existing attributes are kept');
        self::assertCount(0, $c->filter('#p_email_counter .nowo-form-kit-counter__limit'), 'no limit: count only');
        self::assertNull($c->filter('#p_email_counter')->attr('data-nowo-form-kit-counter-max'));
        self::assertSame('Caracteres: %count%', $c->filter('#p_email_counter')->attr('data-nowo-form-kit-counter-text-count'));
        self::assertSame('Mínimo 5 caracteres.', $c->filter('#p_note_counter')->text());
        self::assertNull($c->filter('#p_note_counter')->attr('data-nowo-form-kit-counter-threshold'));
    }

    public function testTheSameExtensionsServeDifferentFormsWithoutLeakingState(): void
    {
        $factory = $this->factory();
        $first   = $factory->createNamedBuilder('a', FormType::class)->add('t', TextType::class, ['char_counter' => ['max' => 10]])->getForm();
        $second  = $factory->createNamedBuilder('b', FormType::class)->add('t', TextType::class)->getForm();

        for ($i = 0; $i < 50; ++$i) {
            $a = $this->render($first, 'form_div_layout.html.twig');
            $b = $this->render($second, 'form_div_layout.html.twig');
        }

        self::assertStringContainsString('a_t_counter', $a);
        self::assertStringNotContainsString('counter', $b);
        self::assertSame(1, substr_count($a, 'data-nowo-form-kit-counter-max="10"'));
    }

    public function testAllNewStringsExistInTheSevenLocales(): void
    {
        foreach (['en', 'es', 'fr', 'de', 'it', 'pt', 'nl'] as $locale) {
            $catalogue = $this->translator($locale)->getCatalogue($locale);
            foreach (['limit.max', 'limit.min', 'limit.range', 'count.max', 'count.min', 'count.plain', 'remaining', 'over'] as $key) {
                self::assertTrue($catalogue->defines('char_counter.' . $key, 'NowoFormKitBundle'), $locale . ' char_counter.' . $key);
            }
        }
    }

    private function assertCspSafe(string $html): void
    {
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
    }

    private function factory(bool $extensions = true): FormFactoryInterface
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $builder   = Forms::createFormFactoryBuilder()->addExtension(new ValidatorExtension($validator));
        if ($extensions) {
            $builder->addTypeExtensions([
                new ChoiceCardsExtension(),
                new SwitchExtension(),
                new CharCounterExtension($validator),
                new CheckboxRowExtension(['default' => []], 'default'),
            ]);
        }

        return $builder->getFormFactory();
    }

    /**
     * @return FormInterface<mixed>
     */
    private function fixtureForm(FormFactoryInterface $factory): FormInterface
    {
        $form = $factory->createNamedBuilder('fixture', FormType::class, ['plan' => 'b', 'tags' => ['x']])
            ->add('title', TextType::class, ['help' => 'Short', 'constraints' => [new Length(max: 10)], 'attr' => ['maxlength' => 10]])
            ->add('bio', TextareaType::class, ['required' => false])
            ->add('agree', CheckboxType::class, ['label' => 'Agree %x%', 'label_translation_parameters' => ['%x%' => 'now']])
            ->add('notify', CheckboxType::class, ['help' => 'Weekly', 'required' => false, 'label_attr' => ['class' => 'checkbox-switch']])
            ->add('plan', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a', 'B' => 'b'], 'choice_attr' => ['A' => ['data-a' => '1']]])
            ->add('tags', ChoiceType::class, ['expanded' => true, 'multiple' => true, 'choices' => ['X' => 'x', 'Y' => 'y']])
            ->add('level', ChoiceType::class, ['choices' => ['Low' => 'l'], 'placeholder' => 'Pick'])
            ->getForm();
        $form->get('title')->addError(new FormError('Too long'));

        return $form;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function render(FormInterface $form, string $baseTheme, bool $fieldTheme = true, ?Translator $translator = null): string
    {
        $root = dirname(__DIR__, 3);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($root . '/src/Resources/views', 'NowoFormKitBundle');
        $fs->addPath($root . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/views', 'NowoUiKit');

        $translator ??= $this->translator('en');
        $twig = new Environment(new ChainLoader([new ArrayLoader(['t.twig' => '{{ form_widget(form) }}']), $fs]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new ThemeExtension(new Theme(), translator: $translator));

        // Same order as twig.form_themes with the prepended FormKit theme: the base layout, FormKit, then the host's.
        $themes = ['form_div_layout.html.twig'];
        if ($fieldTheme) {
            $themes[] = FormKitExtension::FIELD_WIDGETS_FORM_THEME;
        }
        if ($baseTheme !== 'form_div_layout.html.twig') {
            $themes[] = $baseTheme;
        }
        $engine = new TwigRendererEngine($themes, $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine),
        ]));

        return $twig->render('t.twig', ['form' => $form->createView()]);
    }

    private function translator(string $locale): Translator
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        foreach (glob(dirname(__DIR__, 3) . '/src/Resources/translations/*.yaml') ?: [] as $file) {
            $parts = explode('.', basename($file));
            $translator->addResource('yaml', $file, $parts[1], $parts[0]);
        }

        return $translator;
    }
}

/**
 * Data class whose Length constraint the counter reads from the validator metadata.
 */
final class FieldWidgetsArticle
{
    #[Length(min: 5, max: 120, countUnit: Length::COUNT_BYTES)]
    public ?string $summary = null;

    public ?string $slug = null;
}
