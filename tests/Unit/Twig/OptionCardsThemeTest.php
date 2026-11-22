<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Extension\ChoiceCardsExtension;
use Nowo\FormKitBundle\Form\Extension\UiWidgetExtension;
use Nowo\FormKitBundle\Twig\UiKitPartialTwigExtension;
use Nowo\UiKitBundle\Theme\Theme;
use Nowo\UiKitBundle\Twig\ThemeExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\Loader\ArrayLoader as TranslationArrayLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

use function dirname;
use function is_file;

/**
 * 2.24: the `ui_widget: 'option_cards'` option on an expanded ChoiceType, rendered by the UiKit `option_cards` partial
 * (UiKit 2.64+) and, without it, by FormKit's own cards.
 */
final class OptionCardsThemeTest extends TestCase
{
    private function requireUiKitPartial(): void
    {
        if (!is_file(dirname(__DIR__, 3) . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/views/partials/_option_cards.html.twig')) {
            self::markTestSkipped('Needs nowo-tech/ui-kit-bundle 2.64+ (option_cards partial).');
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return FormInterface<mixed>
     */
    private function form(array $options = [], mixed $data = 'pro'): FormInterface
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtensions([new ChoiceCardsExtension(), new UiWidgetExtension()])
            ->getFormFactory();

        return $factory->createNamedBuilder('order', FormType::class, ['plan' => $data])
            ->add('plan', ChoiceType::class, $options + [
                'label'                        => 'Plan for %who%',
                'label_translation_parameters' => ['%who%' => 'Ana'],
                'expanded'                     => true,
                'choices'                      => ['Basic' => 'basic', 'Pro' => 'pro', 'Team' => 'team'],
                'choice_translation_domain'    => 'cards',
                'choice_attr'                  => static fn (string $choice, string $key, string $value): array => match ($value) {
                    'basic' => ['meta' => 'price.basic', 'description' => 'desc.basic', 'icon' => 'home', 'badges' => ['badge.popular'], 'data-x' => 'y'],
                    'team'  => ['disabled' => true, 'description' => 'Not yet'],
                    default => [],
                },
                'ui_widget'         => 'option_cards',
                'ui_widget_options' => ['columns' => 3, 'variant' => 'icon'],
            ])
            ->getForm();
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function render(FormInterface $form, bool $withUiKit = true, string $widget = '{{ form_row(form.plan) }}'): string
    {
        $root = dirname(__DIR__, 3);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($root . '/src/Resources/views', 'NowoFormKitBundle');
        $fs->addPath($root . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/views', 'NowoUiKit');
        $fs->addPath($root . '/vendor/nowo-tech/ui-kit-bundle/src/Resources/views', 'NowoUiKitBundle');

        $translator = new Translator('en');
        $translator->addLoader('array', new TranslationArrayLoader());
        $translator->addResource('array', ['price.basic' => '$9', 'desc.basic' => 'For one person', 'badge.popular' => 'Popular', 'Basic' => 'Basic plan'], 'en', 'cards');

        $twig = new Environment(new ChainLoader([new ArrayLoader(['t.twig' => $widget]), $fs]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new UiKitPartialTwigExtension());
        if ($withUiKit) {
            $twig->addExtension(new ThemeExtension(new Theme(), translator: $translator));
        }
        $engine = new TwigRendererEngine(['form_div_layout.html.twig', FormKitExtension::FIELD_WIDGETS_FORM_THEME], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine)]));

        return $twig->render('t.twig', ['form' => $form->createView()]);
    }

    public function testRendersTheUiKitPartialWithChoiceAttrKeys(): void
    {
        $this->requireUiKitPartial();
        $html = $this->render($this->form());
        $c    = new Crawler($html);

        $set = $c->filter('fieldset#order_plan[data-nowo-ui-option-cards]');
        self::assertCount(1, $set);
        self::assertStringContainsString('nowo-ui-option-cards--icon nowo-ui-option-cards--cols-3', (string) $set->attr('class'));
        self::assertSame('order_plan_label', $set->attr('aria-labelledby'));
        self::assertCount(1, $c->filter('label#order_plan_label'), 'the row label names the group; no legend');
        self::assertCount(0, $c->filter('legend'));

        $cards = $c->filter('label.nowo-ui-option-card');
        self::assertCount(3, $cards);
        $inputs = $c->filter('input[type="radio"]');
        self::assertSame(['order[plan]', 'order[plan]', 'order[plan]'], $inputs->each(static fn (Crawler $i): string => (string) $i->attr('name')));
        self::assertSame(['basic', 'pro', 'team'], $inputs->each(static fn (Crawler $i): string => (string) $i->attr('value')));
        self::assertSame([false, true, false], $inputs->each(static fn (Crawler $i): bool => $i->attr('checked') !== null));
        self::assertNotNull($inputs->eq(0)->attr('required'), 'a required single choice is browser-required');

        $first = $cards->eq(0);
        self::assertSame('Basic plan', trim($first->filter('.nowo-ui-option-card__title')->text()));
        self::assertSame('$9', trim($first->filter('.nowo-ui-option-card__meta')->text()));
        self::assertSame('For one person', trim($first->filter('.nowo-ui-option-card__description')->text()));
        self::assertSame(['Popular'], $first->filter('.nowo-ui-option-card__badges > span')->each(static fn (Crawler $b): string => trim($b->text())));
        self::assertCount(1, $first->filter('.nowo-ui-option-card__icon'));
        // Card keys never reach the input; other choice_attr keys do.
        $input = $first->filter('input');
        self::assertSame('y', $input->attr('data-x'));
        foreach (['description', 'meta', 'icon', 'badges'] as $key) {
            self::assertNull($input->attr($key), $key);
        }
        // Disabled by choice_attr; its description is untranslated text of the domain.
        self::assertNotNull($inputs->eq(2)->attr('disabled'));
        self::assertSame('Not yet', trim($cards->eq(2)->filter('.nowo-ui-option-card__description')->text()));
        self::assertStringNotContainsString('style=', $html);
    }

    public function testMultipleIsCheckboxesAndColumnsAndVariantDefaults(): void
    {
        $this->requireUiKitPartial();
        $form = $this->form(['multiple' => true, 'ui_widget_options' => [], 'choice_translation_domain' => false, 'choice_attr' => []], ['pro']);
        $c    = new Crawler($this->render($form));

        self::assertStringContainsString('nowo-ui-option-cards--basic nowo-ui-option-cards--cols-2', (string) $c->filter('fieldset')->attr('class'));
        $inputs = $c->filter('input[type="checkbox"]');
        self::assertCount(3, $inputs);
        self::assertSame('order[plan][]', $inputs->eq(0)->attr('name'));
        self::assertNull($inputs->eq(0)->attr('required'), 'checkbox groups are not browser-required');
    }

    public function testStandaloneFieldPartialReadsChoiceAttrWithoutTheOption(): void
    {
        $this->requireUiKitPartial();
        $form = $this->form(['ui_widget' => null, 'ui_widget_options' => [], 'choice_cards' => false, 'choice_translation_domain' => false, 'choice_attr' => ['Basic' => ['meta' => '$9', 'icon' => 'home'], 'Pro' => ['disabled' => true]], 'attr' => ['aria-label' => 'Own']]);
        $c    = new Crawler($this->render($form, true, "{{ include('@NowoFormKitBundle/form/_option_cards_field.html.twig', {field: form.plan, columns: 4, variant: 'icon'}, with_context: false) }}"));

        self::assertStringContainsString('nowo-ui-option-cards--icon nowo-ui-option-cards--cols-4', (string) $c->filter('fieldset')->attr('class'));
        self::assertSame('Own', $c->filter('fieldset')->attr('aria-label'));
        self::assertNull($c->filter('fieldset')->attr('aria-labelledby'));
        self::assertSame('$9', trim($c->filter('.nowo-ui-option-card__meta')->text()));
        self::assertNotNull($c->filter('input[value="pro"]')->attr('disabled'));
        self::assertNull($c->filter('input[value="basic"]')->attr('meta'), 'card keys are not printed on the input');
    }

    public function testFallsBackToFormKitCardsWithoutUiKit(): void
    {
        $form = $this->form(['choice_translation_domain' => false]);
        $html = $this->render($form, false);
        $c    = new Crawler($html);

        self::assertCount(0, $c->filter('[data-nowo-ui-option-cards]'));
        self::assertCount(1, $c->filter('#order_plan.nowo-form-kit-choice-cards--cols-3'));
        self::assertCount(3, $c->filter('input[type="radio"].nowo-form-kit-choice-card__input'));
        self::assertNull($c->filter('#order_plan_0')->attr('data-meta'));
        self::assertStringContainsString('desc.basic', $html);
    }

    public function testStandalonePartialWithoutUiKitPrintsTheWidget(): void
    {
        $form = $this->form(['ui_widget' => null, 'ui_widget_options' => [], 'choice_attr' => []]);
        $html = $this->render($form, false, "{{ include('@NowoFormKitBundle/form/_option_cards_field.html.twig', {field: form.plan}, with_context: false) }}");

        self::assertCount(3, (new Crawler($html))->filter('input[type="radio"]'));
    }

    public function testErrorsStayInTheRow(): void
    {
        $this->requireUiKitPartial();
        $form = $this->form();
        $form->get('plan')->addError(new FormError('Pick a plan'));
        $c = new Crawler($this->render($form));

        self::assertStringContainsString('Pick a plan', $c->filter('ul li')->text());
        self::assertCount(1, $c->filter('fieldset'));
    }

    public function testUiKitPartialLookupResolvesTheTemplate(): void
    {
        $template = '@NowoUiKitBundle/partials/_option_cards.html.twig';
        $env      = static function (callable $partial) use ($template): Environment {
            $twig = new Environment(new ArrayLoader([$template => '']));
            $twig->addFunction(new TwigFunction('nowo_ui_partial', $partial));

            return $twig;
        };
        $extension = new UiKitPartialTwigExtension();

        self::assertSame($template, $extension->partial($env(static fn (string $name): string => $template), 'option_cards'));
        self::assertNull($extension->partial($env(static fn (string $name): string => ''), 'option_cards'), 'an empty template name is no partial');
        self::assertNull($extension->partial($env(static function (string $name): string {
            throw new RuntimeException('unknown partial');
        }), 'option_cards'), 'an older UiKit throws for an unknown partial');

        $noFile = new Environment(new ArrayLoader([]));
        $noFile->addFunction(new TwigFunction('nowo_ui_partial', static fn (string $name): string => $template));
        self::assertNull($extension->partial($noFile, 'option_cards'), 'the partial file of an older UiKit is missing');
    }

    public function testUiKitPartialLookupIsNullForUnknownPartials(): void
    {
        $twig = new Environment(new ArrayLoader([]));
        self::assertNull((new UiKitPartialTwigExtension())->partial($twig, 'nope'));
        self::assertNull((new UiKitPartialTwigExtension())->partial($twig, 'option_cards'), 'no UiKit function: null');
        self::assertSame('nowo_form_kit_ui_partial', (new UiKitPartialTwigExtension())->getFunctions()[0]->getName());
    }
}
