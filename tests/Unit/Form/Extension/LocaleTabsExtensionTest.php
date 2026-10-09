<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\LocaleTabsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function count;

final class LocaleTabsExtensionTest extends TestCase
{
    public function testExtendsFormType(): void
    {
        self::assertSame([FormType::class], [...LocaleTabsExtension::getExtendedTypes()]);
    }

    public function testDisabledByDefault(): void
    {
        $view = $this->factory()->createNamed('slugs', FormType::class)->createView();

        self::assertArrayNotHasKey('locale_tabs', $view->vars);
        self::assertNotContains(LocaleTabsExtension::BLOCK_PREFIX, $view->vars['block_prefixes']);
    }

    public function testAddsVarsAndBlockPrefixBeforeUniquePrefix(): void
    {
        $form = $this->factory()->createNamedBuilder('root', FormType::class)
            ->add('slugs', FormType::class, ['locale_tabs' => ['locales' => ['es', 'en']]])
            ->getForm();
        $form->get('slugs')->add('es', TextType::class)->add('en', TextType::class);

        $view     = $form->createView()['slugs'];
        $prefixes = $view->vars['block_prefixes'];

        self::assertSame(['locales' => ['es', 'en']], $view->vars['locale_tabs']);
        self::assertSame(LocaleTabsExtension::BLOCK_PREFIX, $prefixes[count($prefixes) - 2]);
        self::assertSame('_root_slugs', $prefixes[count($prefixes) - 1]);
    }

    public function testTrueNormalizesToEmptyOptions(): void
    {
        $view = $this->factory()->createNamed('slugs', FormType::class, null, ['locale_tabs' => true])->createView();

        self::assertSame([], $view->vars['locale_tabs']);
    }

    public function testBuildViewIsIdempotentForBlockPrefix(): void
    {
        $form      = $this->factory()->createNamed('slugs', FormType::class, null, ['locale_tabs' => []]);
        $view      = $form->createView();
        $extension = new LocaleTabsExtension();
        $extension->buildView($view, $form, $form->getConfig()->getOptions());

        self::assertCount(1, array_keys($view->vars['block_prefixes'], LocaleTabsExtension::BLOCK_PREFIX, true));
    }

    public function testRejectsInvalidOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory()->createNamed('slugs', FormType::class, null, ['locale_tabs' => 'yes']);
    }

    private function factory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeExtension(new LocaleTabsExtension())->getFormFactory();
    }
}
