<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\Compiler\LocaleTabsLocalesPass;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use Nowo\FormKitBundle\NowoFormKitBundle;
use Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_map;
use function get_class;

final class LocaleTabsConfigTest extends TestCase
{
    public function testDefaultsRegisterTheTypeAndTheTwigExtension(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([], $container);

        self::assertSame([], $container->getParameter('nowo_form_kit.locale_tabs.locales'));
        self::assertNull($container->getParameter('nowo_form_kit.locale_tabs.default_locale'));
        self::assertSame('auto', $container->getParameter('nowo_form_kit.locale_tabs.renderer'));
        self::assertTrue($container->getDefinition(TranslatableMapType::class)->isAutoconfigured());
        $twig = $container->getDefinition(LocaleTabsTwigExtension::class);
        self::assertTrue($twig->isAutoconfigured());
        self::assertSame(['%nowo_form_kit.locale_tabs.locales%', '%nowo_form_kit.locale_tabs.default_locale%', null], $twig->getArguments());
    }

    public function testConfiguredLocalesAndRenderer(): void
    {
        foreach (['native' => false, 'ui_kit' => null] as $renderer => $flag) {
            $container = new ContainerBuilder();
            (new FormKitExtension())->load([['locale_tabs' => ['locales' => ['es', 'en'], 'default_locale' => 'es', 'renderer' => $renderer]]], $container);
            self::assertSame(['es', 'en'], $container->getParameter('nowo_form_kit.locale_tabs.locales'));
            self::assertSame('es', $container->getParameter('nowo_form_kit.locale_tabs.default_locale'));
            self::assertSame($flag, $container->getDefinition(LocaleTabsTwigExtension::class)->getArgument(2));
        }
    }

    public function testPassFillsFrameworkLocales(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.enabled_locales', ['es', 'en']);
        $container->setParameter('kernel.default_locale', 'es');
        $container->setParameter('nowo_form_kit.locale_tabs.locales', []);
        $container->setParameter('nowo_form_kit.locale_tabs.default_locale', null);
        (new LocaleTabsLocalesPass())->process($container);
        self::assertSame(['es', 'en'], $container->getParameter('nowo_form_kit.locale_tabs.locales'));
        self::assertSame('es', $container->getParameter('nowo_form_kit.locale_tabs.default_locale'));

        // Configured values win.
        $container = new ContainerBuilder();
        $container->setParameter('kernel.enabled_locales', ['de']);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('nowo_form_kit.locale_tabs.locales', ['fr']);
        $container->setParameter('nowo_form_kit.locale_tabs.default_locale', 'fr');
        (new LocaleTabsLocalesPass())->process($container);
        self::assertSame(['fr'], $container->getParameter('nowo_form_kit.locale_tabs.locales'));
        self::assertSame('fr', $container->getParameter('nowo_form_kit.locale_tabs.default_locale'));

        // Odd framework values are ignored.
        $container = new ContainerBuilder();
        $container->setParameter('kernel.enabled_locales', 'x');
        $container->setParameter('kernel.default_locale', 5);
        $container->setParameter('nowo_form_kit.locale_tabs.locales', []);
        (new LocaleTabsLocalesPass())->process($container);
        self::assertSame([], $container->getParameter('nowo_form_kit.locale_tabs.locales'));
        self::assertNull($container->getParameter('nowo_form_kit.locale_tabs.default_locale'));

        $empty = new ContainerBuilder();
        (new LocaleTabsLocalesPass())->process($empty);
        self::assertFalse($empty->hasParameter('nowo_form_kit.locale_tabs.locales'));
    }

    public function testBundleRegistersThePass(): void
    {
        $container = new ContainerBuilder();
        (new NowoFormKitBundle())->build($container);
        self::assertContains(LocaleTabsLocalesPass::class, array_map(get_class(...), $container->getCompilerPassConfig()->getBeforeOptimizationPasses()));
    }

    public function testPrependRegistersTheFormThemeUnlessDisabled(): void
    {
        $container = $this->withTwig();
        (new FormKitExtension())->prepend($container);
        self::assertSame([['form_themes' => [FormKitExtension::LOCALE_TABS_FORM_THEME]]], $container->getExtensionConfig('twig'));

        $container = $this->withTwig();
        $container->prependExtensionConfig('nowo_form_kit', ['locale_tabs' => ['form_theme' => false]]);
        (new FormKitExtension())->prepend($container);
        self::assertSame([], $container->getExtensionConfig('twig'));

        $noTwig = new ContainerBuilder();
        $noTwig->registerExtension(new FrameworkExtension());
        (new FormKitExtension())->prepend($noTwig);
        self::assertFalse($noTwig->hasExtension('twig'));
    }

    private function withTwig(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new TwigExtension());

        return $container;
    }
}
