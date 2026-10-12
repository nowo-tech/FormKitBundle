<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\UnlabelledChoiceAriaLabelExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UnlabelledChoiceAriaLabelExtensionTest extends TestCase
{
    public function testExtendsChoiceType(): void
    {
        self::assertSame([ChoiceType::class], [...UnlabelledChoiceAriaLabelExtension::getExtendedTypes()]);
    }

    public function testNamesUnlabelledSelectsFromHelpThenPlaceholder(): void
    {
        $extension = $this->extension();

        $help = $this->view(['label' => false, 'help' => 'f.level.help', 'placeholder' => 'f.level.placeholder', 'translation_domain' => 'form']);
        $extension->finishView($help, $this->createStub(FormInterface::class), []);
        self::assertSame('form:f.level.help', $help->vars['attr']['aria-label']);

        $placeholder = $this->view(['label' => false, 'help' => null, 'placeholder' => 'f.type.placeholder', 'translation_domain' => null, 'attr' => ['class' => 'input']]);
        $extension->finishView($placeholder, $this->createStub(FormInterface::class), []);
        self::assertSame(['class' => 'input', 'aria-label' => 'messages:f.type.placeholder'], $placeholder->vars['attr']);

        $untranslated = $this->view(['label' => false, 'help' => 'Raw help', 'translation_domain' => false]);
        $extension->finishView($untranslated, $this->createStub(FormInterface::class), []);
        self::assertSame('Raw help', $untranslated->vars['attr']['aria-label']);

        $noAttr       = new FormView();
        $noAttr->vars = ['label' => false, 'placeholder' => 'Any', 'attr' => 'invalid'];
        $extension->finishView($noAttr, $this->createStub(FormInterface::class), []);
        self::assertSame(['aria-label' => 'messages:Any'], $noAttr->vars['attr']);
    }

    public function testUsesRawTextWithoutTranslator(): void
    {
        $view = $this->view(['label' => false, 'help' => 'f.level.help', 'translation_domain' => 'form']);
        (new UnlabelledChoiceAriaLabelExtension())->finishView($view, $this->createStub(FormInterface::class), []);

        self::assertSame('f.level.help', $view->vars['attr']['aria-label']);
    }

    public function testLeavesLabelledExpandedNamedOrUnnamableFieldsAlone(): void
    {
        $extension = $this->extension();
        foreach ([
            ['label' => 'f.level.label', 'help' => 'h'],
            ['label' => null, 'help' => 'h'],
            ['label' => false, 'expanded' => true, 'help' => 'h'],
            ['label' => false, 'help' => 'h', 'attr' => ['aria-label' => 'Per page']],
            ['label' => false, 'help' => 'h', 'attr' => ['aria-labelledby' => 'x']],
            ['label' => false, 'help' => '', 'placeholder' => false],
            ['label' => false],
        ] as $vars) {
            $view   = $this->view($vars);
            $before = $view->vars['attr'];
            $extension->finishView($view, $this->createStub(FormInterface::class), []);
            self::assertSame($before, $view->vars['attr']);
        }
    }

    public function testIsIdempotent(): void
    {
        $extension = $this->extension();
        $view      = $this->view(['label' => false, 'placeholder' => 'Any']);
        $extension->finishView($view, $this->createStub(FormInterface::class), []);
        $extension->finishView($view, $this->createStub(FormInterface::class), []);

        self::assertSame(['aria-label' => 'messages:Any'], $view->vars['attr']);
    }

    public function testAppliesThroughTheFormFactory(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension($this->extension())
            ->getFormFactory();

        $form = $factory->createNamedBuilder('filter')
            ->add('level', ChoiceType::class, ['label' => false, 'required' => false, 'placeholder' => 'filter.level.any', 'choices' => ['A' => 'a']])
            ->add('status', ChoiceType::class, ['label' => false, 'help' => 'filter.status.help', 'translation_domain' => false, 'choices' => ['A' => 'a']])
            ->add('labelled', ChoiceType::class, ['placeholder' => 'Any', 'required' => false, 'choices' => ['A' => 'a']])
            ->add('radios', ChoiceType::class, ['label' => false, 'expanded' => true, 'help' => 'h', 'choices' => ['A' => 'a']])
            ->getForm();
        $view = $form->createView();

        self::assertSame('messages:filter.level.any', $view['level']->vars['attr']['aria-label']);
        self::assertSame('filter.status.help', $view['status']->vars['attr']['aria-label']);
        self::assertArrayNotHasKey('aria-label', $view['labelled']->vars['attr']);
        self::assertArrayNotHasKey('aria-label', $view['radios']->vars['attr']);
    }

    private function extension(): UnlabelledChoiceAriaLabelExtension
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = [], ?string $domain = null): string => ($domain ?? 'messages') . ':' . $id,
        );

        return new UnlabelledChoiceAriaLabelExtension($translator);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function view(array $vars): FormView
    {
        $view       = new FormView();
        $view->vars = $vars + ['attr' => []];

        return $view;
    }
}
