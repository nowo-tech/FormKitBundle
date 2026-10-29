<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\DependencyInjection;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\Form\Extension\CheckboxRowExtension;
use Nowo\FormKitBundle\Form\Extension\HelpModalExtension;
use Nowo\FormKitBundle\Form\Extension\InputGroupExtension;
use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use Nowo\FormKitBundle\Form\Extension\RequiredLabelSuffixExtension;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use Nowo\FormKitBundle\Form\Extension\UnlabelledChoiceAriaLabelExtension;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use Nowo\FormKitBundle\Form\Type\SearchQueryType;
use Nowo\FormKitBundle\Form\Type\TranslatableMapType;
use Nowo\FormKitBundle\Twig\CsrfActionFormExtension;
use Nowo\FormKitBundle\Twig\LocaleTabsTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Twig\Extension\ExtensionInterface;

/**
 * REQ-SF-003: the bundle relies on the autoconfiguration that FrameworkBundle (form types / type extensions) and
 * TwigBundle (Twig extensions) register, instead of tagging those services by hand. This test registers the same
 * instanceof rules and checks every bundle service still ends up with the tag Symfony needs.
 */
final class AutoconfigurationTest extends TestCase
{
    public function testFormTypesExtensionsAndTwigExtensionsAreTaggedThroughAutoconfiguration(): void
    {
        $container = $this->compileWithAutoconfiguration([]);

        foreach ([CsrfOnlyType::class, HiddenFieldsCsrfType::class, SearchQueryType::class, TranslatableMapType::class] as $id) {
            self::assertSame([[]], $container->getDefinition($id)->getTag('form.type'), $id);
        }

        foreach ([
            InputGroupExtension::class,
            LocaleTabsExtension::class,
            CheckboxRowExtension::class,
            RequiredLabelSuffixExtension::class,
            HelpModalExtension::class,
            UnlabelledChoiceAriaLabelExtension::class,
        ] as $id) {
            self::assertSame([[]], $container->getDefinition($id)->getTag('form.type_extension'), $id);
        }

        foreach ([LocaleTabsTwigExtension::class, CsrfActionFormExtension::class] as $id) {
            self::assertSame([[]], $container->getDefinition($id)->getTag('twig.extension'), $id);
        }
    }

    public function testStatelessCsrfExtensionsKeepTheirSingleExplicitTag(): void
    {
        $container = $this->compileWithAutoconfiguration([
            'stateless_csrf' => ['enabled' => true, 'form_types' => ['App\\Form\\NewsletterType']],
        ]);

        $definition = $container->getDefinition('nowo_form_kit.stateless_csrf_extension.0');
        self::assertSame(StatelessCsrfTokenIdExtension::class, $definition->getClass());
        self::assertFalse($definition->isAutoconfigured());
        self::assertSame([['extended_type' => 'App\\Form\\NewsletterType']], $definition->getTag('form.type_extension'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compileWithAutoconfiguration(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerForAutoconfiguration(FormTypeInterface::class)->addTag('form.type');
        $container->registerForAutoconfiguration(FormTypeExtensionInterface::class)->addTag('form.type_extension');
        $container->registerForAutoconfiguration(ExtensionInterface::class)->addTag('twig.extension');

        (new FormKitExtension())->load([$config], $container);
        // Same order as PassConfig: ResolveClassPass (id-as-class services) runs before ResolveInstanceofConditionalsPass.
        (new ResolveClassPass())->process($container);
        (new ResolveInstanceofConditionalsPass())->process($container);

        return $container;
    }
}
