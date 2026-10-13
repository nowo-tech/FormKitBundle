<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Integration\Form;

use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Tests\Stubs\PlaceholderTestStatusType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\ResolvedFormTypeFactory;

/**
 * Placeholder semantics of FormOptionsMerger, checked on a real form factory (built views).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormOptionsMergerPlaceholderTest extends TestCase
{
    private FormRegistry $registry;
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        $this->registry = new FormRegistry(
            [new CoreExtension(), new PreloadedExtension([new PlaceholderTestStatusType()], [])],
            new ResolvedFormTypeFactory(),
        );
        $this->factory = new FormFactory($this->registry);
    }

    public function testExplicitRootPlaceholderWinsOverConventionKey(): void
    {
        $view = $this->buildField(TextType::class, ['placeholder' => 'contact.name.custom']);

        self::assertSame('contact.name.custom', $view->vars['attr']['placeholder']);
    }

    public function testExplicitAttrPlaceholderWinsOverExplicitRootPlaceholder(): void
    {
        $view = $this->buildField(TextType::class, ['placeholder' => 'root.key', 'attr' => ['placeholder' => 'attr.key']]);

        self::assertSame('attr.key', $view->vars['attr']['placeholder']);
    }

    public function testConventionKeyStillAppliedWhenNothingIsSet(): void
    {
        $view = $this->buildField(TextType::class);

        self::assertSame('contact.field.placeholder', $view->vars['attr']['placeholder']);
    }

    public function testProfileDefaultsPlaceholderWinsOverConventionKey(): void
    {
        $view = $this->buildField(TextType::class, [], ['placeholder' => 'Type here']);

        self::assertSame('Type here', $view->vars['attr']['placeholder']);
    }

    public function testFieldTypesPlaceholderWinsOverConventionKey(): void
    {
        $view = $this->buildField(TextType::class, [], [], ['text' => ['placeholder' => 'text.default']]);

        self::assertSame('text.default', $view->vars['attr']['placeholder']);
    }

    public function testExplicitFalseRemovesPlaceholderFromTextField(): void
    {
        $view = $this->buildField(TextType::class, ['placeholder' => false]);

        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testChoiceRootPlaceholderIsTheEmptyOptionLabel(): void
    {
        $view = $this->buildField(ChoiceType::class, ['choices' => ['A' => 'a'], 'required' => false, 'placeholder' => 'contact.field.empty']);

        self::assertSame('contact.field.empty', $view->vars['placeholder']);
        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testChoicePlaceholderFalseRemovesTheEmptyOption(): void
    {
        $view = $this->buildField(ChoiceType::class, ['choices' => ['A' => 'a'], 'required' => false, 'placeholder' => false]);

        self::assertNull($view->vars['placeholder']);
        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testExpandedChoicePlaceholderFalseRendersNoNoneRadio(): void
    {
        $view = $this->buildField(ChoiceType::class, [
            'choices'     => ['A' => 'a', 'B' => 'b'],
            'expanded'    => true,
            'required'    => false,
            'placeholder' => false,
        ]);

        self::assertCount(2, $view->children);
        self::assertArrayNotHasKey('placeholder', $view->children);
    }

    public function testChoiceWithoutPlaceholderKeepsSymfonyDefaultAndNoConventionAttr(): void
    {
        $view = $this->buildField(ChoiceType::class, ['choices' => ['A' => 'a'], 'required' => false]);

        self::assertSame('', $view->vars['placeholder']);
        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testChoiceIgnoresGenericProfilePlaceholderButUsesFieldTypesPlaceholder(): void
    {
        $generic = $this->buildField(ChoiceType::class, ['choices' => ['A' => 'a'], 'required' => false], ['placeholder' => false]);
        self::assertSame('', $generic->vars['placeholder']);
        self::assertArrayNotHasKey('placeholder', $generic->vars['attr']);

        $typed = $this->buildField(ChoiceType::class, ['choices' => ['A' => 'a'], 'required' => false], [], ['choice' => ['placeholder' => 'choice.pick']]);
        self::assertSame('choice.pick', $typed->vars['placeholder']);
    }

    public function testChoiceSubtypeIsDetectedThroughTheRegistry(): void
    {
        $view = $this->buildField(PlaceholderTestStatusType::class, ['required' => false, 'placeholder' => 'status.any']);

        self::assertSame('status.any', $view->vars['placeholder']);
        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testChoiceSnakeCaseTypeIsDetectedThroughTheTypeMap(): void
    {
        $merged = $this->merger()->resolve('contact', 'country', 'country', ['placeholder' => 'contact.country.empty']);

        self::assertSame('contact.country.empty', $merged['placeholder']);
        self::assertArrayNotHasKey('placeholder', $merged['attr']);
    }

    public function testChoiceShortNamesAreDetectedWithoutRegistry(): void
    {
        $merger = new FormOptionsMerger($this->profiles([], []), 'default', new ConstraintDefinitionFactory());

        $merged = $merger->resolve('contact', 'owner', 'entity', ['placeholder' => false]);

        self::assertFalse($merged['placeholder']);
        self::assertArrayNotHasKey('placeholder', $merged['attr']);
    }

    /**
     * @return iterable<string, array{class-string<FormTypeInterface<mixed>>}>
     */
    public static function noPlaceholderTypes(): iterable
    {
        yield 'checkbox' => [CheckboxType::class];
        yield 'radio' => [RadioType::class];
        yield 'hidden' => [HiddenType::class];
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     */
    #[DataProvider('noPlaceholderTypes')]
    public function testCheckboxRadioAndHiddenGetNoConventionPlaceholder(string $type): void
    {
        $view = $this->buildField($type);

        self::assertArrayNotHasKey('placeholder', $view->vars['attr']);
    }

    public function testCheckboxSnakeCaseNameGetsNoConventionPlaceholder(): void
    {
        $merged = $this->merger()->resolve('contact', 'agree', 'checkbox');

        self::assertArrayNotHasKey('placeholder', $merged['attr']);
        self::assertSame('contact.agree.label', $merged['label']);
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $options
     * @param array<string, mixed> $profileDefaults
     * @param array<string, array<string, mixed>> $fieldTypes
     */
    private function buildField(string $type, array $options = [], array $profileDefaults = [], array $fieldTypes = []): FormView
    {
        $merged = $this->merger($profileDefaults, $fieldTypes)->resolve('contact', 'field', $type, $options);

        return $this->factory->createBuilder(FormType::class)
            ->add('field', $type, $merged)
            ->getForm()
            ->createView()['field'];
    }

    /**
     * @param array<string, mixed> $profileDefaults
     * @param array<string, array<string, mixed>> $fieldTypes
     */
    private function merger(array $profileDefaults = [], array $fieldTypes = []): FormOptionsMerger
    {
        return new FormOptionsMerger(
            $this->profiles($profileDefaults, $fieldTypes),
            'default',
            new ConstraintDefinitionFactory(),
            $this->registry,
            new FormTypeMap([]),
        );
    }

    /**
     * @param array<string, mixed> $profileDefaults
     * @param array<string, array<string, mixed>> $fieldTypes
     *
     * @return array<string, array{translation_domain: string, defaults: array{attr: array<string, mixed>, row_attr: array<string, mixed>}, field_types: array<string, array<string, mixed>>}>
     */
    private function profiles(array $profileDefaults, array $fieldTypes): array
    {
        return [
            'default' => [
                'translation_domain' => 'form',
                'defaults'           => array_merge(['attr' => ['class' => 'form-control'], 'row_attr' => []], $profileDefaults),
                'field_types'        => $fieldTypes,
            ],
        ];
    }
}
