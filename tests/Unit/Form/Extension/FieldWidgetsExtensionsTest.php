<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\CharCounterExtension;
use Nowo\FormKitBundle\Form\Extension\ChoiceCardsExtension;
use Nowo\FormKitBundle\Form\Extension\SwitchExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;

/**
 * 2.22: option validation and view variables of ChoiceCardsExtension, SwitchExtension and CharCounterExtension.
 */
final class FieldWidgetsExtensionsTest extends TestCase
{
    public function testExtendedTypes(): void
    {
        self::assertSame([ChoiceType::class], [...ChoiceCardsExtension::getExtendedTypes()]);
        self::assertSame([CheckboxType::class], [...SwitchExtension::getExtendedTypes()]);
        self::assertSame([TextType::class], [...CharCounterExtension::getExtendedTypes()]);
    }

    public function testOptionsOffLeaveTheViewUntouched(): void
    {
        $plain = Forms::createFormFactory()->createNamedBuilder('f', FormType::class)
            ->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a']])
            ->add('s', CheckboxType::class)
            ->add('t', TextareaType::class)
            ->getForm()->createView();
        $kit = $this->factory()->createNamedBuilder('f', FormType::class)
            ->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a']])
            ->add('s', CheckboxType::class)
            ->add('t', TextareaType::class)
            ->getForm()->createView();

        foreach (['c', 's', 't'] as $name) {
            self::assertSame($plain[$name]->vars['block_prefixes'], $kit[$name]->vars['block_prefixes'], $name);
            self::assertSame($plain[$name]->vars['label_attr'], $kit[$name]->vars['label_attr'], $name);
            self::assertArrayNotHasKey('choice_cards', $kit[$name]->vars);
            self::assertArrayNotHasKey('switch', $kit[$name]->vars);
            self::assertArrayNotHasKey('char_counter', $kit[$name]->vars);
        }
    }

    public function testBlockPrefixesAreInsertedOnceBeforeTheUniquePrefix(): void
    {
        $view = $this->view([
            ['c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'choice_cards' => true]],
            ['s', CheckboxType::class, ['switch' => true]],
            ['t', TextareaType::class, ['char_counter' => true]],
        ]);

        self::assertSame(['form', 'choice', 'nowo_choice_cards', '_f_c'], $view['c']->vars['block_prefixes']);
        self::assertSame(['form', 'checkbox', 'nowo_switch', '_f_s'], $view['s']->vars['block_prefixes']);
        self::assertSame(['form', 'text', 'textarea', 'nowo_char_counter', '_f_t'], $view['t']->vars['block_prefixes']);

        // A second pass (e.g. a type that already carries the prefix) does not duplicate it.
        foreach ([[new ChoiceCardsExtension(), 'c', 'finishView'], [new SwitchExtension(), 's', 'buildView'], [new CharCounterExtension(), 't', 'buildView']] as [$extension, $name, $method]) {
            $form    = $this->factory()->createNamedBuilder('f', FormType::class)->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'choice_cards' => true])->add('s', CheckboxType::class, ['switch' => true])->add('t', TextareaType::class, ['char_counter' => true])->getForm();
            $options = $form->get($name)->getConfig()->getOptions();
            $before  = $view[$name]->vars['block_prefixes'];
            $extension->{$method}($view[$name], $form->get($name), $options);
            self::assertSame($before, $view[$name]->vars['block_prefixes'], $name);
        }
    }

    public function testChoiceCardsVars(): void
    {
        $view = $this->view([['plan', ChoiceType::class, [
            'expanded'     => true,
            'label'        => false,
            'choices'      => ['Basic' => 'basic', 'Pro' => 'pro'],
            'choice_cards' => ['description' => static fn (string $choice, string $key, string $value): string => $key . ':' . $value . ':' . $choice, 'columns' => 2],
        ]]]);

        self::assertSame(['columns' => 2], $view['plan']->vars['choice_cards']);
        self::assertNull($view['plan']->vars['choice_cards_label_id']);
        self::assertSame('f_plan_label', $view['plan']->vars['label_attr']['id']);
        self::assertSame(['description' => 'Basic:basic:basic', 'icon_html' => null, 'image' => null], $view['plan']['0']->vars['choice_card']);
        self::assertSame('Pro:pro:pro', $view['plan']['1']->vars['choice_card']['description']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidChoiceCards(): iterable
    {
        yield 'not expanded' => [['choice_cards' => true], 'requires "expanded" to be true'];
        yield 'unknown key' => [['expanded' => true, 'choice_cards' => ['foo' => 1]], 'Invalid "choice_cards" option'];
        yield 'columns out of range' => [['expanded' => true, 'choice_cards' => ['columns' => 5]], 'Invalid "choice_cards" option'];
        yield 'description type' => [['expanded' => true, 'choice_cards' => ['description' => 'x']], 'Invalid "choice_cards" option'];
        yield 'description not a string' => [['expanded' => true, 'choice_cards' => ['description' => ['a' => 3]]], 'must return a string or null for choice "a"'];
        yield 'image without alt' => [['expanded' => true, 'choice_cards' => ['image' => ['a' => ['src' => '/a.png']]]], 'use "alt" => "" for a decorative image'];
        yield 'image without src' => [['expanded' => true, 'choice_cards' => ['image' => ['a' => ['src' => '', 'alt' => '']]]], 'non-empty string'];
        yield 'image not an array' => [['expanded' => true, 'choice_cards' => ['image' => static fn (): string => '/a.png']], 'must return null or'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidChoiceCards')]
    public function testInvalidChoiceCards(array $options, string $message): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage($message);

        $this->view([['c', ChoiceType::class, $options + ['choices' => ['A' => 'a']]]]);
    }

    public function testChoiceCardsWithoutAChoiceListUseTheChildName(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class)
            ->add('c', ChoiceType::class, ['expanded' => true, 'choices' => ['A' => 'a'], 'choice_cards' => ['description' => static fn (mixed $choice, int|string $key): string => (string) $key]])
            ->getForm();
        $view    = $form->createView();
        $view    = $view['c'];
        $options = $form->get('c')->getConfig()->getOptions();
        $stub    = $this->createStub(FormInterface::class);
        $config  = $this->createStub(FormConfigInterface::class);
        $config->method('getAttribute')->willReturn(null);
        $stub->method('getConfig')->willReturn($config);

        (new ChoiceCardsExtension())->finishView($view, $stub, $options);
        self::assertSame('0', $view['0']->vars['choice_card']['description']);
    }

    public function testSwitchVarsAndValidation(): void
    {
        $view = $this->view([['a', CheckboxType::class, ['switch' => true]], ['b', CheckboxType::class, ['switch' => ['size' => 'sm', 'icons' => true]]]]);
        self::assertSame(['size' => 'md', 'icons' => false], $view['a']->vars['switch']);
        self::assertSame(['size' => 'sm', 'icons' => true], $view['b']->vars['switch']);

        foreach ([['size' => 'xl'], ['icons' => 'yes'], ['foo' => true]] as $invalid) {
            try {
                $this->view([['a', CheckboxType::class, ['switch' => $invalid]]]);
                self::fail('expected an exception');
            } catch (InvalidOptionsException $e) {
                self::assertStringContainsString('Invalid "switch" option', $e->getMessage());
            }
        }
    }

    public function testSwitchIsRejectedOnRadios(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('not on radios');

        $this->view([['r', RadioType::class, ['switch' => true]]]);
    }

    public function testCharCounterVarsAndValidation(): void
    {
        $view = $this->view([
            ['a', TextType::class, ['char_counter' => ['max' => 5]]],
            ['b', TextType::class, ['char_counter' => ['max' => 200, 'threshold' => 0, 'unit' => 'bytes']]],
            ['c', TextType::class, ['char_counter' => true, 'attr' => ['maxlength' => 0, 'minlength' => 4]]],
            ['d', TextType::class, ['char_counter' => true, 'attr' => ['maxlength' => '']]],
        ]);
        self::assertSame(['max' => 5, 'min' => null, 'unit' => 'graphemes', 'threshold' => 1], $view['a']->vars['char_counter']);
        self::assertSame(['max' => 200, 'min' => null, 'unit' => 'bytes', 'threshold' => 0], $view['b']->vars['char_counter']);
        self::assertSame(['max' => null, 'min' => 4, 'unit' => 'graphemes', 'threshold' => null], $view['c']->vars['char_counter']);
        self::assertSame(['max' => null, 'min' => null, 'unit' => 'graphemes', 'threshold' => null], $view['d']->vars['char_counter']);

        foreach ([['max' => 0], ['min' => -1], ['unit' => 'words'], ['threshold' => -1], ['max' => '5']] as $invalid) {
            try {
                $this->view([['a', TextType::class, ['char_counter' => $invalid]]]);
                self::fail('expected an exception');
            } catch (InvalidOptionsException $e) {
                self::assertStringContainsString('Invalid "char_counter" option', $e->getMessage());
            }
        }
    }

    public function testCharCounterIgnoresMetadataItCannotRead(): void
    {
        $throwing = $this->createStub(MetadataFactoryInterface::class);
        $throwing->method('getMetadataFor')->willThrowException(new RuntimeException('no metadata'));
        $notClass = $this->createStub(MetadataFactoryInterface::class);
        $notClass->method('getMetadataFor')->willReturn($this->createStub(MetadataInterface::class));

        foreach ([$throwing, $notClass] as $factory) {
            $form = Forms::createFormFactoryBuilder()->addTypeExtension(new CharCounterExtension($factory))->getFormFactory()
                ->createNamedBuilder('f', FormType::class, null, ['data_class' => stdClass::class])
                ->add('title', TextType::class, ['char_counter' => true, 'mapped' => false, 'property_path' => 'title'])
                ->add('body', TextType::class, ['char_counter' => true, 'property_path' => 'body'])
                ->add('items', TextType::class, ['char_counter' => true, 'property_path' => '[items]'])
                ->add('nested', TextType::class, ['char_counter' => true, 'property_path' => 'a.b'])
                ->getForm();
            $view = $form->createView();
            foreach (['title', 'body', 'items', 'nested'] as $name) {
                self::assertNull($view[$name]->vars['char_counter']['max'], $name);
            }
        }

        $root = Forms::createFormFactoryBuilder()->addTypeExtension(new CharCounterExtension($throwing))->getFormFactory()
            ->createNamed('t', TextType::class, null, ['char_counter' => true])->createView();
        self::assertNull($root->vars['char_counter']['max']);
        $noClass = Forms::createFormFactoryBuilder()->addTypeExtension(new CharCounterExtension($throwing))->getFormFactory()
            ->createNamedBuilder('f')->add('t', TextType::class, ['char_counter' => true])->getForm()->createView();
        self::assertNull($noClass['t']->vars['char_counter']['max']);
    }

    private function factory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addTypeExtensions([new ChoiceCardsExtension(), new SwitchExtension(), new CharCounterExtension()])
            ->getFormFactory();
    }

    /**
     * @param list<array{string, class-string<FormTypeInterface<mixed>>, array<string, mixed>}> $fields
     */
    private function view(array $fields): FormView
    {
        $builder = $this->factory()->createNamedBuilder('f', FormType::class);
        foreach ($fields as [$name, $type, $options]) {
            $builder->add($name, $type, $options);
        }

        return $builder->getForm()->createView();
    }
}
