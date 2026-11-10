<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Nowo\FormKitBundle\Form\Extension\VisibleWhenExtension;
use Nowo\FormKitBundle\Form\VisibleWhenRules;
use Nowo\UiKitBundle\Form\VisibleWhen;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use function iterator_to_array;

enum VisibleWhenTestProvider: string
{
    case S3 = 's3';
    case R2 = 'r2';
}

enum VisibleWhenTestUnit
{
    case A;
}

/**
 * Compound secret like DoctrineEncrypt's SecretType: a value plus a "clear" checkbox.
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class VisibleWhenTestSecretType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', PasswordType::class, ['required' => false])->add('clear', CheckboxType::class, ['required' => false]);
    }
}

/**
 * `visible_when` field option: UiKit row attributes + PRE_SUBMIT keeper (spec 222).
 */
final class VisibleWhenExtensionTest extends TestCase
{
    public function testExtendsEveryType(): void
    {
        self::assertSame([FormType::class], iterator_to_array(VisibleWhenExtension::getExtendedTypes()));
    }

    public function testRowAttrMergedAndMatchesUiKit(): void
    {
        $view = $this->storageForm()->createView();

        $region = $view->children['region']->vars['row_attr'];
        self::assertSame('x', $region['class']);
        self::assertSame('storage[provider]', $region[VisibleWhenRules::FIELD_ATTRIBUTE]);
        self::assertSame('["s3"]', $region[VisibleWhenRules::VALUES_ATTRIBUTE]);
        self::assertSame(VisibleWhen::attributes('storage[provider]', ['s3']), VisibleWhenRules::attributes('storage[provider]', ['s3']));
        self::assertSame('storage[region]', $view->children['custom_region']->vars['row_attr'][VisibleWhenRules::FIELD_ATTRIBUTE]);
        self::assertSame('["1"]', $view->children['secret']->vars['row_attr'][VisibleWhenRules::VALUES_ATTRIBUTE]);
        self::assertArrayNotHasKey(VisibleWhenRules::FIELD_ATTRIBUTE, $view->children['provider']->vars['row_attr']);
    }

    public function testNamesOfMultipleAndUnnamedRoots(): void
    {
        $form = $this->factory()->createNamedBuilder('', FormType::class)
            ->add('tags', ChoiceType::class, ['choices' => ['a' => 'a', 'b' => 'b'], 'multiple' => true])
            ->add('boxes', ChoiceType::class, ['choices' => ['a' => 'a'], 'multiple' => true, 'expanded' => true])
            ->add('t', TextType::class, ['visible_when' => ['field' => 'tags', 'in' => ['a']]])
            ->add('b', TextType::class, ['visible_when' => ['field' => 'boxes', 'in' => ['a']]])
            ->getForm();
        $view = $form->createView();
        self::assertSame('tags[]', $view->children['t']->vars['row_attr'][VisibleWhenRules::FIELD_ATTRIBUTE]);
        self::assertSame('boxes[]', $view->children['b']->vars['row_attr'][VisibleWhenRules::FIELD_ATTRIBUTE]);
    }

    public function testControllerOutsideTheFormIsNamedAndLeftAsSubmitted(): void
    {
        $form = $this->factory()->createNamedBuilder('s', FormType::class)
            ->add('host', TextType::class, ['visible_when' => ['field' => 'missing', 'in' => ['x']]])
            ->getForm();
        self::assertSame('s[missing]', $form->createView()->children['host']->vars['row_attr'][VisibleWhenRules::FIELD_ATTRIBUTE]);
        $form->submit(['host' => 'typed']);
        self::assertSame('typed', $form->get('host')->getData());

        $root = $this->factory()->createNamedBuilder('', FormType::class)
            ->add('host', TextType::class, ['visible_when' => ['field' => 'missing', 'in' => ['x']]])
            ->getForm();
        self::assertSame('missing', $root->createView()->children['host']->vars['row_attr'][VisibleWhenRules::FIELD_ATTRIBUTE]);
    }

    public function testDefaultChangesNothing(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class)->add('a', TextType::class)->getForm();
        self::assertNull($form->get('a')->getConfig()->getOption('visible_when'));
        self::assertSame([], $form->createView()->children['a']->vars['row_attr']);
        $form->submit(['a' => 'x']);
        self::assertSame(['a' => 'x'], $form->getData());
        self::assertSame([], VisibleWhenRules::rules($form));
    }

    public function testHiddenRowKeepsStoredValueAndChainsDropSecrets(): void
    {
        $form = $this->storageForm();
        $form->submit([
            'provider'      => 'r2',
            'region'        => 'typed-region',
            'custom_region' => 'typed-custom',
            'secret'        => ['value' => 'typed-secret'],
            'plain_secret'  => 'typed-plain',
            'enabled'       => '',
            'when'          => ['year' => '2030', 'month' => '1', 'day' => '1'],
        ]);
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $data = $form->getData();
        self::assertSame('r2', $data['provider']->value);
        self::assertSame('other', $data['region']);
        // chained: region hidden → custom region hidden although "other" matches
        self::assertSame('stored-custom', $data['custom_region']);
        self::assertSame(['value' => null, 'clear' => false], $data['secret']);
        self::assertNull($data['plain_secret']);
        self::assertSame('2026-01-02', $data['when']->format('Y-m-d'));
    }

    public function testVisibleRowsTakeTheSubmittedValues(): void
    {
        $form = $this->storageForm();
        $form->submit([
            'provider'      => 's3',
            'region'        => 'other',
            'custom_region' => 'typed-custom',
            'secret'        => ['value' => 'typed-secret'],
            'plain_secret'  => 'typed-plain',
            'enabled'       => '1',
            'when'          => ['year' => '2030', 'month' => '1', 'day' => '1'],
        ]);
        $data = $form->getData();
        self::assertSame('typed-custom', $data['custom_region']);
        self::assertSame('typed-secret', $data['secret']['value']);
        self::assertSame('typed-plain', $data['plain_secret']);
        self::assertSame('2030-01-01', $data['when']->format('Y-m-d'));
    }

    public function testHiddenSecretKeepsAnExplicitClear(): void
    {
        $form = $this->storageForm();
        $form->submit(['provider' => 's3', 'region' => 'other', 'secret' => ['value' => 'x', 'clear' => '1'], 'enabled' => '']);
        self::assertSame(['value' => null, 'clear' => true], $form->getData()['secret']);
    }

    public function testChainIsOrderIndependentAndDisabledControllerUsesItsValue(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class, ['p' => 'a', 'c' => 'kept', 'm' => 'x'])
            ->add('c', TextType::class, ['visible_when' => ['field' => 'm', 'in' => ['x']]])
            ->add('m', TextType::class, ['visible_when' => ['field' => 'p', 'in' => ['b']]])
            ->add('p', TextType::class, ['disabled' => true])
            ->add('self', TextType::class, ['visible_when' => ['field' => 'self', 'in' => ['x']]])
            ->getForm();
        $form->submit(['p' => 'b', 'c' => 'typed', 'm' => 'x', 'self' => 'y']);
        self::assertSame(['p' => 'a', 'c' => 'kept', 'm' => 'x', 'self' => 'y'], $form->getData());
    }

    public function testMultipleControllerMatchesAnyValue(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class, ['tags' => [], 'a' => 'kept'])
            ->add('tags', ChoiceType::class, ['choices' => ['x' => 'x', 'y' => 'y'], 'multiple' => true, 'expanded' => true])
            ->add('a', TextType::class, ['visible_when' => ['field' => 'tags', 'in' => ['y']]])
            ->getForm();
        $form->submit(['tags' => ['x', 'y'], 'a' => 'typed']);
        self::assertSame('typed', $form->getData()['a']);

        $form = $this->factory()->createNamedBuilder('f', FormType::class, ['tags' => [], 'a' => 'kept'])
            ->add('tags', ChoiceType::class, ['choices' => ['x' => 'x', 'y' => 'y'], 'multiple' => true, 'expanded' => true])
            ->add('a', TextType::class, ['visible_when' => ['field' => 'tags', 'in' => ['']]])
            ->getForm();
        $form->submit(['tags' => [['nested']], 'a' => 'typed']);
        self::assertSame('typed', $form->getData()['a']);
    }

    public function testHiddenExpandedChoiceKeepsItsValue(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class, ['p' => 'a', 'mode' => 'x'])
            ->add('p', TextType::class)
            ->add('mode', ChoiceType::class, ['choices' => ['x' => 'x', 'y' => 'y'], 'expanded' => true, 'visible_when' => ['field' => 'p', 'in' => ['a']]])
            ->getForm();
        $form->submit(['p' => 'b', 'mode' => 'y']);
        self::assertTrue($form->isValid());
        self::assertSame('x', $form->getData()['mode']);
    }

    public function testNormalizeRefusesNonArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        VisibleWhenRules::normalize('x');
    }

    public function testNonArraySubmissionIsIgnored(): void
    {
        $form = $this->storageForm();
        $form->submit(null);
        self::assertTrue($form->isSubmitted());
    }

    public function testSelfReferenceFailsOnRender(): void
    {
        $form = $this->factory()->createNamedBuilder('f', FormType::class)
            ->add('a', TextType::class, ['visible_when' => ['field' => 'a', 'in' => ['x']]])
            ->getForm();
        $this->expectException(LogicException::class);
        $form->createView();
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'not an array' => ['x'];
        yield 'unknown key' => [['field' => 'a', 'in' => ['x'], 'when' => 1]];
        yield 'no field' => [['in' => ['x']]];
        yield 'bad field' => [['field' => 'a"b', 'in' => ['x']]];
        yield 'blank field' => [['field' => ' ', 'in' => ['x']]];
        yield 'no in' => [['field' => 'a']];
        yield 'empty in' => [['field' => 'a', 'in' => []]];
        yield 'object in' => [['field' => 'a', 'in' => [new stdClass()]]];
        yield 'unit enum' => [['field' => 'a', 'in' => [VisibleWhenTestUnit::A]]];
        yield 'secret not bool' => [['field' => 'a', 'in' => ['x'], 'secret' => 'yes']];
    }

    /**
     * @dataProvider invalidOptions
     */
    public function testInvalidOptionsAreRefused(mixed $option): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory()->createNamedBuilder('f', FormType::class)->add('b', TextType::class, ['visible_when' => $option])->getForm();
    }

    public function testNormalizeAndValues(): void
    {
        self::assertSame(['field' => 'p', 'in' => ['1', '', 's3', '2'], 'secret' => false], VisibleWhenRules::normalize(['field' => 'p', 'in' => [true, false, VisibleWhenTestProvider::S3, 2, '2']]));
        self::assertSame('', VisibleWhenRules::visibilityValue(null));
        self::assertSame('', VisibleWhenRules::visibilityValue([]));
        self::assertSame('1.5', VisibleWhenRules::visibilityValue(1.5));
    }

    private function factory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeExtension(new VisibleWhenExtension())->getFormFactory();
    }

    /**
     * @return FormInterface<mixed>
     */
    private function storageForm(): FormInterface
    {
        return $this->factory()->createNamedBuilder('storage', FormType::class, [
            'provider'      => VisibleWhenTestProvider::S3,
            'region'        => 'other',
            'custom_region' => 'stored-custom',
            'plain_secret'  => 'stored-plain',
            'enabled'       => true,
            'when'          => new DateTimeImmutable('2026-01-02'),
        ])
            ->add('custom_region', TextType::class, ['required' => true, 'visible_when' => ['field' => 'region', 'in' => ['other']]])
            ->add('provider', EnumType::class, ['class' => VisibleWhenTestProvider::class])
            ->add('region', ChoiceType::class, [
                'choices'      => ['eu' => 'eu-west-1', 'other' => 'other'],
                'row_attr'     => ['class' => 'x'],
                'visible_when' => ['field' => 'provider', 'in' => [VisibleWhenTestProvider::S3]],
            ])
            ->add('enabled', CheckboxType::class, ['required' => false])
            ->add('secret', VisibleWhenTestSecretType::class, ['visible_when' => ['field' => 'enabled', 'in' => [true], 'secret' => true]])
            ->add('plain_secret', PasswordType::class, ['required' => false, 'visible_when' => ['field' => 'provider', 'in' => ['s3'], 'secret' => true]])
            ->add('when', DateType::class, ['widget' => 'choice', 'years' => [2026, 2030], 'input' => 'datetime_immutable', 'visible_when' => ['field' => 'provider', 'in' => ['s3']]])
            ->getForm();
    }
}
