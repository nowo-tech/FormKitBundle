<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Controller;

use InvalidArgumentException;
use Nowo\FormKitBundle\Controller\FormKitControllerTrait;
use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

final class FormKitControllerTraitTest extends TestCase
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
                    'field_types' => [
                        'text'   => ['attr' => ['class' => 'form-control form-control-text']],
                        'choice' => ['attr' => ['class' => 'form-select form-select-choice']],
                    ],
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

    public function testAddTextTypeUsesFixedFormName(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addText(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
            {
                $this->addTextType($builder, $fieldName, $options, $configName, $formName);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static fn(array $options): bool => $options['translation_domain'] === 'messages'
                    && $options['label'] === 'controller_contact.name.label'
                    && $options['help'] === 'controller_contact.name.help'
                    && ($options['attr']['placeholder'] ?? null) === 'controller_contact.name.placeholder'
                    && ($options['attr']['class'] ?? '') === 'form-control form-control-text'
                    && ($options['row_attr']['class'] ?? '') === 'mb-3'),
            );

        $subject->addText($builder, 'name');
    }

    public function testAddTextTypeFormNameArgumentOverridesFixedValue(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addTextField(FormBuilderInterface $builder, string $fieldName, array $options = [], ?string $configName = null, ?string $formName = null): void
            {
                $this->addTextType($builder, $fieldName, $options, $configName, $formName);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static fn(array $options): bool => $options['label'] === 'other_form.name.label'),
            );

        $subject->addTextField($builder, 'name', [], null, 'other_form');
    }

    public function testBuildFormFromArraySupportsSnakeCaseTypes(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function build(FormBuilderInterface $builder, array $fields, ?string $configName = null, ?string $formName = null): void
            {
                $this->buildFormFromArray($builder, $fields, $configName, $formName);
            }
        };

        $subject->setFormKitFormName('search_form');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::logicalOr('q', 'topic'),
                self::logicalOr(TextType::class, ChoiceType::class),
                self::isType('array'),
            );

        $subject->build($builder, [
            'q'     => 'text',
            'topic' => [
                'type'    => 'choice',
                'choices' => ['Support' => 'support'],
            ],
        ]);
    }

    public function testThrowsWhenNoFormNameProvided(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addTextField(FormBuilderInterface $builder, string $fieldName): void
            {
                $this->addTextType($builder, $fieldName);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^FormKitControllerTrait requires a form name/');

        $subject->addTextField($builder, 'name');
    }

    public function testThrowsForUnknownSnakeCaseType(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addFieldTypePublic(FormBuilderInterface $builder, string $fieldName, string $type): void
            {
                $this->addFieldType($builder, $fieldName, $type);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Unknown form type "missing_type"/');

        $subject->addFieldTypePublic($builder, 'name', 'missing_type');
    }

    public function testPhase2AddSimpleTypesCallsBuilderAddWithResolvedFqcn(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addAny(FormBuilderInterface $builder, string $name, string $method): void
            {
                match ($method) {
                    'text' => $this->addTextType($builder, $name),
                    'email' => $this->addEmailType($builder, $name),
                    'textarea' => $this->addTextareaType($builder, $name),
                    'password' => $this->addPasswordType($builder, $name),
                    'url' => $this->addUrlType($builder, $name),
                    'integer' => $this->addIntegerType($builder, $name),
                    'number' => $this->addNumberType($builder, $name),
                    'checkbox' => $this->addCheckboxType($builder, $name),
                    'choice' => $this->addChoiceType($builder, $name),
                    default => throw new \InvalidArgumentException('unknown'),
                };
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $calls = [];
        $builder->expects(self::exactly(9))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$calls, $builder) {
                self::assertIsArray($opts);
                $calls[] = [$name, $type];

                return $builder;
            });

        $subject->addAny($builder, 'name', 'text');
        $subject->addAny($builder, 'email', 'email');
        $subject->addAny($builder, 'message', 'textarea');
        $subject->addAny($builder, 'password', 'password');
        $subject->addAny($builder, 'url', 'url');
        $subject->addAny($builder, 'age', 'integer');
        $subject->addAny($builder, 'price', 'number');
        $subject->addAny($builder, 'agree', 'checkbox');
        $subject->addAny($builder, 'topic', 'choice');

        self::assertSame([
            ['name', TextType::class],
            ['email', EmailType::class],
            ['message', TextareaType::class],
            ['password', PasswordType::class],
            ['url', UrlType::class],
            ['age', IntegerType::class],
            ['price', NumberType::class],
            ['agree', CheckboxType::class],
            ['topic', ChoiceType::class],
        ], $calls);
    }

    public function testAddSwitchTypeHorizontalAndVerticalAttachesTransformerAndSetsChoiceTranslationDomain(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addSwitchPublic(FormBuilderInterface $b, string $field, array $cfg = []): void
            {
                $this->addSwitchType($b, $field, $cfg);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);
        $transformers = [];
        $adds = [];

        $child->expects(self::exactly(2))
            ->method('addModelTransformer')
            ->willReturnCallback(static function ($t) use (&$transformers, $child) {
                $transformers[] = $t;

                return $child;
            });

        $builder->expects(self::exactly(2))
            ->method('get')
            ->willReturn($child);

        $builder->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$adds, $builder) {
                $adds[] = ['name' => $name, 'type' => $type, 'opts' => $opts];

                return $builder;
            });

        $subject->addSwitchPublic($builder, 'isActive', [
            'label_position' => 'horizontal',
            'switch_value' => 1,
        ]);

        $subject->addSwitchPublic($builder, 'isActive2', [
            'label_position' => 'vertical',
            'switch_value' => 1,
        ]);

        self::assertCount(2, $adds);
        self::assertSame('isActive', $adds[0]['name']);
        self::assertSame(ChoiceType::class, $adds[0]['type']);
        self::assertSame('messages', $adds[0]['opts']['choice_translation_domain'] ?? null);
        self::assertArrayNotHasKey('label_position', $adds[0]['opts']);

        self::assertSame('isActive2', $adds[1]['name']);
        self::assertSame(ChoiceType::class, $adds[1]['type']);
        self::assertSame(false, $adds[1]['opts']['choice_label'] ?? null);
        self::assertArrayNotHasKey('label_position', $adds[1]['opts']);

        self::assertCount(2, $transformers);
        self::assertContainsOnlyInstancesOf(SwitchModelTransformer::class, $transformers);
    }

    public function testAddJsonTypeAttachesTransformer(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addJsonPublic(FormBuilderInterface $b, string $field, array $cfg = []): void
            {
                $this->addJsonType($b, $field, $cfg);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $builder->expects(self::once())
            ->method('get')
            ->willReturn($child);

        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(JsonModelTransformer::class));

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'payload',
                TextareaType::class,
                self::callback(static fn(array $opts): bool => !array_key_exists('json_pretty', $opts) && !array_key_exists('json_unescaped_unicode', $opts)),
            )
            ->willReturn($builder);

        $subject->addJsonPublic($builder, 'payload', [
            'json_pretty' => false,
            'json_unescaped_unicode' => false,
        ]);
    }

    public function testAddBoolMoneyCsvPresetsAttachCorrectTransformers(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addBoolPublic(FormBuilderInterface $b, string $field, array $cfg = []): void
            {
                $this->addBoolType($b, $field, $cfg);
            }

            public function addMoneyPublic(FormBuilderInterface $b, string $field, array $cfg = []): void
            {
                $this->addMoneyType($b, $field, $cfg);
            }

            public function addCsvPublic(FormBuilderInterface $b, string $field, array $cfg = []): void
            {
                $this->addCsvType($b, $field, $cfg);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $adds = [];
        $transformers = [];

        $builder->expects(self::exactly(3))
            ->method('get')
            ->willReturn($child);

        $child->expects(self::exactly(3))
            ->method('addModelTransformer')
            ->willReturnCallback(static function ($t) use (&$transformers, $child) {
                $transformers[] = $t;

                return $child;
            });

        $builder->expects(self::exactly(3))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$adds, $builder) {
                $adds[] = [$name, $type];
                self::assertIsArray($opts);

                return $builder;
            });

        $subject->addBoolPublic($builder, 'enabled', ['on_value' => 1, 'off_value' => 0]);
        $subject->addMoneyPublic($builder, 'amount', ['money_scale' => 2]);
        $subject->addCsvPublic($builder, 'tags', ['csv_separator' => ';']);

        self::assertSame([
            ['enabled', CheckboxType::class],
            ['amount', TextType::class],
            ['tags', TextareaType::class],
        ], $adds);

        self::assertCount(3, $transformers);
        self::assertContainsOnlyInstancesOf(\Symfony\Component\Form\DataTransformerInterface::class, $transformers);
        self::assertInstanceOf(BoolModelTransformer::class, $transformers[0]);
        self::assertInstanceOf(MoneyModelTransformer::class, $transformers[1]);
        self::assertInstanceOf(CsvModelTransformer::class, $transformers[2]);
    }

    public function testResolveFormNameUsesGetBlockPrefixWhenNoFixedOrArgumentIsProvided(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function getBlockPrefix(): string
            {
                return 'block_prefix_form';
            }

            public function addTextPublic(FormBuilderInterface $b, string $field): void
            {
                $this->addTextType($b, $field);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static fn(array $opts): bool => ($opts['label'] ?? null) === 'block_prefix_form.name.label'),
            );

        $subject->addTextPublic($builder, 'name');
    }

    public function testAddFieldTypeReturnsTypeUnchangedWhenGivenAnFqcn(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            public function addFieldPublic(FormBuilderInterface $b, string $field, string $type): void
            {
                $this->addFieldType($b, $field, $type);
            }
        };

        $subject->setFormKitFormName('controller_contact');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::isType('array'),
            );

        $subject->addFieldPublic($builder, 'name', TextType::class);
    }

    public function testAddTranslationsUsesResolverMethod(): void
    {
        $merger = $this->createMerger();
        $map    = new FormTypeMap([]);

        $subject = new class($merger, $map) {
            use FormKitControllerTrait;

            public function __construct(FormOptionsMerger $merger, FormTypeMap $map)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormTypeMap($map);
            }

            /** @return array{default_locale: string, enabled_locales: array<int, string>} */
            protected function resolveFormKitTranslationsLocaleContext(array $options): array
            {
                return [
                    'default_locale' => 'es',
                    'enabled_locales' => ['es', 'en'],
                ];
            }

            public function addTranslationsPublic(FormBuilderInterface $builder, array $options): void
            {
                $this->addTranslations($builder, $options);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn(array $opts): bool => $opts['form_type'] === 'App\\Form\\TranslationItemType'
                    && $opts['default_locale'] === 'es'
                    && $opts['enabled_locales'] === ['es', 'en']
                    && $opts['required_locales'] === ['es']),
            )
            ->willReturn($builder);

        $subject->addTranslationsPublic($builder, [
            'form_type' => 'App\\Form\\TranslationItemType',
        ]);
    }
}
