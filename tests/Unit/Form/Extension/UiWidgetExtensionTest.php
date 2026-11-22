<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\ChoiceCardsExtension;
use Nowo\FormKitBundle\Form\Extension\UiWidgetExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function count;

/**
 * 2.24: option validation and view variables of UiWidgetExtension (`ui_widget`, `ui_widget_options`, choice_attr keys).
 */
final class UiWidgetExtensionTest extends TestCase
{
    private function factory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeExtensions([new ChoiceCardsExtension(), new UiWidgetExtension()])->getFormFactory();
    }

    public function testExtendedTypes(): void
    {
        self::assertSame([ChoiceType::class], [...UiWidgetExtension::getExtendedTypes()]);
    }

    public function testOffLeavesTheViewUntouched(): void
    {
        $view = $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'choice_attr' => ['A' => ['meta' => 'x']]])->getForm()->createView();

        self::assertArrayNotHasKey('ui_widget', $view['c']->vars);
        self::assertNotContains(UiWidgetExtension::BLOCK_PREFIX, $view['c']->vars['block_prefixes']);
        self::assertSame('x', $view['c'][0]->vars['attr']['meta'], 'without the option choice_attr is untouched');
    }

    public function testViewVarsAndStrippedAttributes(): void
    {
        $view = $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, [
            'expanded'          => true,
            'choices'           => ['A' => 'a', 'B' => 'b'],
            'label'             => false,
            'choice_attr'       => ['A' => ['meta' => '$9', 'description' => 'D', 'icon' => 'home', 'badges' => ['x'], 'data-k' => 'v'], 'B' => ['meta' => '', 'badges' => []]],
            'ui_widget'         => 'option_cards',
            'ui_widget_options' => ['columns' => 4, 'variant' => 'icon'],
        ])->getForm()->createView()['c'];

        self::assertSame(['name' => 'option_cards', 'columns' => 4, 'variant' => 'icon'], $view->vars['ui_widget']);
        self::assertContains(UiWidgetExtension::BLOCK_PREFIX, $view->vars['block_prefixes']);
        $prefixes = $view->vars['block_prefixes'];
        self::assertIsArray($prefixes);
        self::assertSame(UiWidgetExtension::BLOCK_PREFIX, $prefixes[count($prefixes) - 2], 'just before the unique prefix');
        self::assertNull($view->vars['choice_cards_label_id'], 'label: false');
        self::assertSame(['columns' => 4], $view->vars['choice_cards']);
        self::assertSame(['data-k' => 'v'], $view[0]->vars['attr']);
        self::assertSame(['description' => 'D', 'meta' => '$9', 'icon' => 'home', 'badges' => ['x']], $view[0]->vars['option_card'] + []);
        self::assertSame(['meta' => null, 'badges' => [], 'description' => null, 'icon' => null], $view[1]->vars['option_card']);
        self::assertSame(['description' => 'D', 'icon_html' => null, 'image' => null], $view[0]->vars['choice_card']);
    }

    public function testLabelIdIsKept(): void
    {
        $view = $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'ui_widget' => 'option_cards', 'label_attr' => ['id' => 'mine']])->getForm()->createView()['c'];

        self::assertSame('mine', $view->vars['choice_cards_label_id']);
        $again = $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'ui_widget' => 'option_cards'])->getForm()->createView()['c'];
        self::assertSame('f_c_label', $again->vars['choice_cards_label_id']);
        self::assertSame(['name' => 'option_cards', 'columns' => 2, 'variant' => 'basic'], $again->vars['ui_widget']);
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptions')]
    public function testInvalidOptions(array $options, string $message): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage($message);
        $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, $options + ['choices' => ['A' => 'a']])->getForm()->createView();
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'not expanded' => [['ui_widget' => 'option_cards'], 'requires "expanded"'];
        yield 'with choice_cards' => [['expanded' => true, 'ui_widget' => 'option_cards', 'choice_cards' => true], 'cannot be combined'];
        yield 'columns out of range' => [['expanded' => true, 'ui_widget' => 'option_cards', 'ui_widget_options' => ['columns' => 5]], 'Invalid "ui_widget_options"'];
        yield 'unknown variant' => [['expanded' => true, 'ui_widget' => 'option_cards', 'ui_widget_options' => ['variant' => 'x']], 'Invalid "ui_widget_options"'];
        yield 'unknown option' => [['expanded' => true, 'ui_widget' => 'option_cards', 'ui_widget_options' => ['nope' => 1]], 'Invalid "ui_widget_options"'];
        yield 'badges not a list' => [['expanded' => true, 'ui_widget' => 'option_cards', 'choice_attr' => ['A' => ['badges' => 'x']]], 'must be a list of strings'];
        yield 'badge not a string' => [['expanded' => true, 'ui_widget' => 'option_cards', 'choice_attr' => ['A' => ['badges' => [1]]]], 'must be a list of strings'];
        yield 'meta not a string' => [['expanded' => true, 'ui_widget' => 'option_cards', 'choice_attr' => ['A' => ['meta' => 9]]], 'must be a string'];
    }
}
