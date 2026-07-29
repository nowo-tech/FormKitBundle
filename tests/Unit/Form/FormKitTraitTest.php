<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use LogicException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormKitTrait;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

use function dirname;

final class FormKitTraitTest extends TestCase
{
    private function createMerger(): FormOptionsMerger
    {
        return new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [],
                ],
                'compact' => [
                    'translation_domain' => 'compact_forms',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control-sm'],
                        'row_attr' => ['class' => 'mb-1'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
    }

    private function createType(): FormKitTraitTestType
    {
        return new FormKitTraitTestType();
    }

    public function testAddFieldResolvesSnakeCaseAndAppliesConfig(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));
        $type->setFormKitConfigName('compact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'q',
                TextType::class,
                self::callback(static fn (array $options): bool => $options['translation_domain'] === 'compact_forms'
                    && $options['label'] === 'search_form.q.label'
                    && ($options['attr']['class'] ?? '') === 'form-control-sm'),
            );

        $type->addSnakeField($builder, 'q', 'text');
    }

    public function testAddFieldThrowsForUnknownType(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown form type snake_case name "missing_type".');
        $type->addSnakeField($builder, 'foo', 'missing_type');
    }

    public function testBuildFormFromArraySupportsMixedDefinitions(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::logicalOr('q', 'topic'),
                self::logicalOr(
                    TextType::class,
                    ChoiceType::class,
                ),
                self::isType('array'),
            );

        $type->addFromArray($builder, [
            'q'     => 'text',
            'topic' => [
                'type'    => 'choice',
                'choices' => ['Support' => 'support'],
            ],
        ]);
    }

    public function testBuildFormFromArrayThrowsWhenTypeIsMissing(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "broken" must have a non-empty "type" key.');

        $type->addFromArray($builder, [
            'broken' => ['choices' => ['a' => 'A']],
        ]);
    }

    public function testPhase2AddHelpersDelegateToAddField(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);
        $calls   = [];
        $builder->expects(self::exactly(9))
            ->method('add')
            ->willReturnCallback(static function ($name, $fqcn, $opts) use (&$calls, $builder): MockObject {
                $calls[] = [$name, $fqcn];
                self::assertIsArray($opts);

                return $builder;
            });

        $type->callAddText($builder, 'text');
        $type->callAddEmail($builder, 'email');
        $type->callAddTextarea($builder, 'textarea');
        $type->callAddPassword($builder, 'password');
        $type->callAddUrl($builder, 'url');
        $type->callAddInteger($builder, 'integer');
        $type->callAddNumber($builder, 'number');
        $type->callAddCheckbox($builder, 'checkbox');
        $type->callAddChoice($builder, 'choice');

        self::assertSame([
            ['text', TextType::class],
            ['email', EmailType::class],
            ['textarea', TextareaType::class],
            ['password', PasswordType::class],
            ['url', UrlType::class],
            ['integer', IntegerType::class],
            ['number', NumberType::class],
            ['checkbox', CheckboxType::class],
            ['choice', ChoiceType::class],
        ], $calls);
    }

    public function testWithBuilderAddFieldHelpersOmitBuilderArgument(): void
    {
        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);
        $calls   = [];
        $builder->expects(self::exactly(9))
            ->method('add')
            ->willReturnCallback(static function ($name, $fqcn, $opts) use (&$calls, $builder): FormBuilderInterface {
                $calls[] = [$name, $fqcn];
                self::assertIsArray($opts);

                return $builder;
            });

        $type->buildWithBoundHelpers($builder);

        self::assertSame([
            ['text', TextType::class],
            ['email', EmailType::class],
            ['textarea', TextareaType::class],
            ['password', PasswordType::class],
            ['url', UrlType::class],
            ['integer', IntegerType::class],
            ['number', NumberType::class],
            ['checkbox', CheckboxType::class],
            ['choice', ChoiceType::class],
        ], $calls);
    }

    public function testBoundBuilderThrowsOutsideWithBuilder(): void
    {
        $type = new class {
            use FormKitTrait;

            public function getBlockPrefix(): string
            {
                return 'search_form';
            }

            public function callBoundBuilder(): void
            {
                $this->boundBuilder();
            }
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No form builder is bound');
        $type->callBoundBuilder();
    }

    public function testBoundBuilderHelpersCoverNamedArrayAndOptionalFields(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

        $type = $this->createType();
        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormTypeMap(new FormTypeMap([]));

        $builder = $this->createMock(FormBuilderInterface::class);
        $calls   = [];
        $builder->expects(self::exactly(4))
            ->method('add')
            ->willReturnCallback(static function ($name, $fqcn, $opts) use (&$calls, $builder): FormBuilderInterface {
                $calls[] = [$name, $fqcn];
                self::assertIsArray($opts);

                return $builder;
            });

        $type->buildWithOptionalBoundHelpers($builder);

        self::assertSame([
            ['custom', TextType::class],
            ['topic', ChoiceType::class],
            ['document', DropzoneType::class],
            ['avatar', CropperType::class],
        ], $calls);
    }
}

final class FormKitTraitTestType
{
    use FormKitTrait;

    public function getBlockPrefix(): string
    {
        return 'search_form';
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    public function addSnakeField(FormBuilderInterface $builder, string $name, string $type, array $options = []): void
    {
        $this->addField($builder, $name, $type, $options);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $fields
     */
    public function addFromArray(FormBuilderInterface $builder, array $fields): void
    {
        $this->buildFormFromArray($builder, $fields);
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    public function callAddText(FormBuilderInterface $builder, string $name, array $options = []): void
    {
        $this->addText($builder, $name, $options);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddEmail(FormBuilderInterface $builder, string $name): void
    {
        $this->addEmail($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddTextarea(FormBuilderInterface $builder, string $name): void
    {
        $this->addTextarea($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddPassword(FormBuilderInterface $builder, string $name): void
    {
        $this->addPassword($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddUrl(FormBuilderInterface $builder, string $name): void
    {
        $this->addUrl($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddInteger(FormBuilderInterface $builder, string $name): void
    {
        $this->addInteger($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddNumber(FormBuilderInterface $builder, string $name): void
    {
        $this->addNumber($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddCheckbox(FormBuilderInterface $builder, string $name): void
    {
        $this->addCheckbox($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function callAddChoice(FormBuilderInterface $builder, string $name): void
    {
        $this->addChoice($builder, $name);
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function buildWithBoundHelpers(FormBuilderInterface $builder): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addTextField('text');
            $this->addEmailField('email');
            $this->addTextareaField('textarea');
            $this->addPasswordField('password');
            $this->addUrlField('url');
            $this->addIntegerField('integer');
            $this->addNumberField('number');
            $this->addCheckboxField('checkbox');
            $this->addChoiceField('choice');
        });
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function buildWithOptionalBoundHelpers(FormBuilderInterface $builder): void
    {
        $this->withBuilder($builder, function (): void {
            $this->addNamedField('custom', 'text');
            $this->buildFieldsFromArray([
                'topic' => [
                    'type'    => 'choice',
                    'choices' => ['Support' => 'support'],
                ],
            ]);
            $this->addDropzoneField('document');
            $this->addCropperField('avatar');
        });
    }
}
