<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

use function array_column;
use function array_map;

final class LocaleTabsTwigExtensionTest extends TestCase
{
    public function testFunctions(): void
    {
        self::assertSame(['nowo_form_kit_locale_tabs', 'nowo_form_kit_ui_kit_locale_tabs'], array_map(static fn (TwigFunction $f): string => $f->getName(), (new LocaleTabsTwigExtension())->getFunctions()));
    }

    public function testUiKitTemplateDetection(): void
    {
        $ext = new LocaleTabsTwigExtension();
        self::assertNull($ext->uiKitTemplate($this->env()), 'no nowo_ui_partial');
        self::assertNull($ext->uiKitTemplate($this->env(static fn (string $n): string => 'x', false)), 'template missing (UiKit < 2.5)');
        self::assertNull($ext->uiKitTemplate($this->env(static fn (string $n): string => throw new RuntimeException('Unknown partial'))), 'partial unknown');
        self::assertNull($ext->uiKitTemplate($this->env(static fn (string $n): string => '')));
        self::assertSame('host/_lt.html.twig', $ext->uiKitTemplate($this->env(static fn (string $n): string => 'host/_lt.html.twig')), 'theme-replaced partial');
        self::assertNull($ext->uiKitTemplate($this->env(static fn (string $n): string => 'x'), false));
        self::assertNull((new LocaleTabsTwigExtension(uiKit: false))->uiKitTemplate($this->env(static fn (string $n): string => 'x')));
        self::assertSame('x', (new LocaleTabsTwigExtension(uiKit: false))->uiKitTemplate($this->env(static fn (string $n): string => 'x'), true));
    }

    public function testEntriesLocaleFromChildValueDataOrName(): void
    {
        $field           = $this->view(['compound' => true]);
        $field->children = [
            'a'  => $this->entry(['locale' => $this->view(['value' => 'es'])]),
            'b'  => $this->entry(['locale' => $this->view(['value' => ''])], ['data' => ['locale' => 'en']]),
            'c'  => $this->entry(['locale' => $this->view(['value' => null])], ['data' => new class {
                public function getLocale(): string
                {
                    return 'fr';
                }
            }]),
            'it' => $this->entry(['locale' => $this->view(['value' => ''])], ['data' => new stdClass()]),
        ];

        $lt = (new LocaleTabsTwigExtension())->localeTabs($this->env(), $field, ['required_locales' => ['en', 7, ['x']]]);

        self::assertSame('entries', $lt['mode']);
        self::assertSame(['es', 'en', 'fr', 'it'], $lt['locales']);
        self::assertSame('es', $lt['default_locale']);
        self::assertSame(['en', '7'], $lt['required_locales']);
        self::assertSame([false, true, false, false], array_column($lt['groups'], 'required'));
        self::assertSame('en', $lt['groups'][1]['hidden'][0]['value']);
        self::assertSame([], $lt['groups'][0]['rows']);
        self::assertNull($lt['ui_kit_template']);
    }

    public function testChildrenModeDefaultsDirectionAndExplicitMode(): void
    {
        $field           = $this->view(['compound' => true]);
        $field->children = ['es' => $this->view(['valid' => false, 'attr' => ['class' => 'x']]), 'en' => $this->view([]), 'ar_EG' => $this->view([])];

        $lt = (new LocaleTabsTwigExtension(['en'], 'en'))->localeTabs($this->env(), $field);
        self::assertSame('children', $lt['mode']);
        self::assertSame('en', $lt['default_locale'], 'configured default when listed');
        self::assertSame([true, false, false], array_column($lt['groups'], 'invalid'));
        self::assertSame(['ltr', 'ltr', 'rtl'], array_column($lt['groups'], 'dir'));
        self::assertSame(['aria-invalid' => 'true', 'class' => 'x'], $field->children['es']->vars['attr']);

        self::assertSame('es', (new LocaleTabsTwigExtension([], 'de'))->localeTabs($this->env(), $field)['default_locale'], 'configured default not listed');
        self::assertSame('children', (new LocaleTabsTwigExtension())->localeTabs($this->env(), $this->view(['compound' => true]), ['mode' => 'children'])['mode']);
        self::assertSame('children', (new LocaleTabsTwigExtension())->localeTabs($this->env(), $this->view(['compound' => true]))['mode'], 'no children: not entries');
        self::assertSame('', (new LocaleTabsTwigExtension())->localeTabs($this->env(), $this->view(['compound' => true]))['default_locale']);
    }

    public function testFieldsModeWithoutMatchingFields(): void
    {
        $lt = (new LocaleTabsTwigExtension(['es']))->localeTabs($this->env(), $this->view(['compound' => true]), ['fields' => ['name'], 'locales' => 'not a list']);
        self::assertSame('fields', $lt['mode']);
        self::assertSame(['es'], $lt['locales']);
        self::assertSame([], $lt['groups']);
    }

    private function env(?callable $partial = null, bool $template = true): Environment
    {
        $env = new Environment(new ArrayLoader($template ? [LocaleTabsTwigExtension::UI_KIT_TEMPLATE => ''] : []));
        if ($partial !== null) {
            $env->addFunction(new TwigFunction('nowo_ui_partial', $partial));
        }

        return $env;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function view(array $vars): FormView
    {
        $view       = new FormView();
        $view->vars = $vars + ['valid' => true, 'compound' => false];

        return $view;
    }

    /**
     * @param array<string, FormView> $children
     * @param array<string, mixed>    $vars
     */
    private function entry(array $children, array $vars = []): FormView
    {
        $entry           = $this->view($vars + ['compound' => true]);
        $entry->children = $children;

        return $entry;
    }
}
