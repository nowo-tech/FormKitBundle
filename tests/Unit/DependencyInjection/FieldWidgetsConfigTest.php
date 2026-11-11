<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\Configuration;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Extension\CharCounterExtension;
use Nowo\FormKitBundle\Form\Extension\ChoiceCardsExtension;
use Nowo\FormKitBundle\Form\Extension\SwitchExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * 2.22: the field widgets options (choice_cards, switch, char_counter) and their form theme.
 */
final class FieldWidgetsConfigTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), []);
        self::assertSame(['form_theme' => true], $processed['field_widgets']);

        $processed = (new Processor())->processConfiguration(new Configuration(), [['field_widgets' => ['form_theme' => false]]]);
        self::assertSame(['form_theme' => false], $processed['field_widgets']);
    }

    public function testExtensionsAreRegisteredAndAutoconfigured(): void
    {
        $container = new ContainerBuilder();
        (new FormKitExtension())->load([], $container);

        foreach ([ChoiceCardsExtension::class, SwitchExtension::class, CharCounterExtension::class] as $class) {
            self::assertTrue($container->getDefinition($class)->isAutoconfigured(), $class);
        }
        $metadata = $container->getDefinition(CharCounterExtension::class)->getArgument('$metadataFactory');
        self::assertInstanceOf(Reference::class, $metadata);
        self::assertSame('validator', (string) $metadata);
        self::assertSame(ContainerInterface::IGNORE_ON_INVALID_REFERENCE, $metadata->getInvalidBehavior());
    }

    public function testPrependRegistersTheFormThemeUnlessDisabled(): void
    {
        $container = $this->withTwig();
        (new FormKitExtension())->prepend($container);
        self::assertContains(['form_themes' => [FormKitExtension::FIELD_WIDGETS_FORM_THEME]], $container->getExtensionConfig('twig'));

        $container = $this->withTwig();
        $container->prependExtensionConfig('nowo_form_kit', ['field_widgets' => ['form_theme' => false]]);
        (new FormKitExtension())->prepend($container);
        self::assertNotContains(['form_themes' => [FormKitExtension::FIELD_WIDGETS_FORM_THEME]], $container->getExtensionConfig('twig'));
        self::assertContains(['form_themes' => [FormKitExtension::LOCALE_TABS_FORM_THEME]], $container->getExtensionConfig('twig'));

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
