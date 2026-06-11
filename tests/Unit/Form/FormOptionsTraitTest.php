<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;

final class FormOptionsTraitTest extends TestCase
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
                        'text' => ['attr' => ['class' => 'form-control text-input']],
                    ],
                ],
                'bootstrap' => [
                    'translation_domain' => 'forms',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control form-control-lg'],
                        'row_attr' => ['class' => 'mb-4'],
                    ],
                    'field_types' => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
    }

    public function testAddWithDefaultsUsesSelectedConfig(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addField(FormBuilderInterface $builder): void
            {
                $this->addWithDefaults($builder, 'name', TextType::class, []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormKitConfigName('bootstrap');

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'name',
                TextType::class,
                self::callback(static fn(array $options): bool => $options['translation_domain'] === 'forms'
                    && $options['label'] === 'demo_form.name.label'
                    && ($options['attr']['placeholder'] ?? null) === 'demo_form.name.placeholder'
                    && $options['help'] === 'demo_form.name.help'
                    && ($options['attr']['class'] ?? '') === 'form-control form-control-lg'),
            );

        $type->addField($builder);
    }

    public function testBuildFormFromArraySupportsStringAndArrayDefinitions(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'profile';
            }

            public function addFromArray(FormBuilderInterface $builder): void
            {
                $this->buildFormFromArray($builder, [
                    'full_name' => TextType::class,
                    'topic'     => [
                        'type'    => ChoiceType::class,
                        'choices' => ['Support' => 'support'],
                    ],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::logicalOr('full_name', 'topic'),
                self::logicalOr(TextType::class, ChoiceType::class),
                self::isType('array'),
            );

        $type->addFromArray($builder);
    }

    public function testBuildFormFromArrayThrowsWhenTypeIsMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'profile';
            }

            public function addInvalid(FormBuilderInterface $builder): void
            {
                $this->buildFormFromArray($builder, [
                    'broken' => ['choices' => ['A' => 'a']],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "broken" must have a non-empty "type" key.');
        $type->addInvalid($builder);
    }

    public function testPhase2AddHelpersUseAddWithDefaultsAndAttachTransformers(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);

        $child = $this->createMock(\Symfony\Component\Form\FormBuilder::class);
        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(BoolModelTransformer::class));

        $builder->expects(self::atLeastOnce())
            ->method('get')
            ->willReturn($child);

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addBool(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addBoolType($b, $name, $cfg);
            }

            public function addJson(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addJsonType($b, $name, $cfg);
            }

            public function addMoney(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addMoneyType($b, $name, $cfg);
            }

            public function addCsv(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addCsvType($b, $name, $cfg);
            }

            public function addSwitch(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addSwitchType($b, $name, $cfg);
            }
        };

        // addBoolType (CheckboxType + BoolModelTransformer)
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'enabled',
                \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
                self::isType('array'),
            );

        $subject->addBool($builder, 'enabled', ['on_value' => 1, 'off_value' => 0]);
    }

    public function testAddSwitchTypeHorizontalAndVerticalBuildOptionsAndAttachesTransformer(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $builder->expects(self::exactly(2))
            ->method('get')
            ->willReturn($child);

        $child->expects(self::exactly(2))
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(SwitchModelTransformer::class));

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addSwitchPublic(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addSwitchType($b, $name, $cfg);
            }
        };

        $builder->expects(self::exactly(2))
            ->method('add')
            ->with(
                self::anything(),
                \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class,
                self::callback(static fn(array $opts): bool => !array_key_exists('label_position', $opts)),
            );

        $subject->addSwitchPublic($builder, 'isActive', [
            'label_position' => 'horizontal',
            'switch_value' => 1,
            'row_attr' => ['class' => 'existing-row'],
        ]);

        $subject->addSwitchPublic($builder, 'isActive2', [
            'label_position' => 'vertical',
            'switch_value' => 1,
            'row_attr' => ['class' => 'existing-row-2'],
            'attr' => ['class' => 'existing-switch-attr'],
        ]);
    }

    public function testBuildFormFromArraySupportsArrayDefinitionWithTypeKey(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'profile';
            }

            public function addFromArray(FormBuilderInterface $builder): void
            {
                $this->buildFormFromArray($builder, [
                    'topic' => [
                        'type' => ChoiceType::class,
                        'choices' => ['Support' => 'support'],
                    ],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'topic',
                ChoiceType::class,
                self::isType('array'),
            );

        $type->addFromArray($builder);
    }

    public function testDataTransformerSwitchConfigurationBuilderBranchAddsTransformer(): void
    {
        $merger  = $this->createMerger();
        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addTransformerForSwitch(FormBuilderInterface $b, string $fieldName, int $switchValue): void
            {
                $this->dataTransformerSwitchConfiguration($b, $fieldName, $switchValue);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(SwitchModelTransformer::class));

        $builder->expects(self::once())
            ->method('get')
            ->with('is_active')
            ->willReturn($child);

        $subject->addTransformerForSwitch($builder, 'is_active', 1);
    }

    public function testAddJsonTypeHonorsPrettyAndUnescapedUnicodeFlags(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $builder->expects(self::once())
            ->method('get')
            ->willReturn($child);

        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::callback(static fn($t) => $t instanceof JsonModelTransformer));

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'payload',
                TextareaType::class,
                self::callback(static fn(array $opts): bool => !array_key_exists('json_pretty', $opts) && !array_key_exists('json_unescaped_unicode', $opts)),
            );

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addJsonPublic(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addJsonType($b, $name, $cfg);
            }
        };

        $subject->addJsonPublic($builder, 'payload', [
            'json_pretty' => false,
            'json_unescaped_unicode' => false,
        ]);
    }

    public function testAddMoneyTypeHonorsMoneyScaleAndAttachesTransformer(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $builder->expects(self::once())
            ->method('get')
            ->willReturn($child);

        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(MoneyModelTransformer::class));

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'amount_cents',
                TextType::class,
                self::callback(static function (array $opts): bool {
                    if (array_key_exists('money_scale', $opts)) {
                        return false;
                    }
                    if (isset($opts['attr']['placeholder'])) {
                        return false;
                    }

                    return ($opts['required'] ?? null) === false;
                }),
            );

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addMoneyPublic(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addMoneyType($b, $name, $cfg);
            }
        };

        $subject->addMoneyPublic($builder, 'amount_cents', ['money_scale' => 2, 'placeholder' => 'should strip']);
    }

    public function testAddCsvTypeHonorsSeparatorAndFlagsAndAttachesTransformer(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);
        $child   = $this->createMock(\Symfony\Component\Form\FormBuilder::class);

        $builder->expects(self::once())
            ->method('get')
            ->willReturn($child);

        $child->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(CsvModelTransformer::class));

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'tags',
                TextareaType::class,
                self::callback(static fn(array $opts): bool => !array_key_exists('csv_separator', $opts)),
            );

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addCsvPublic(FormBuilderInterface $b, string $name, array $cfg = []): void
            {
                $this->addCsvType($b, $name, $cfg);
            }
        };

        $subject->addCsvPublic($builder, 'tags', [
            'csv_separator' => ';',
            'csv_trim_tokens' => false,
            'csv_allow_empty_tokens' => true,
        ]);
    }

    public function testPhase2AddTextEmailTextareaPasswordUrlIntegerNumberCheckboxChoice(): void
    {
        $merger  = $this->createMerger();
        $builder = $this->createMock(FormBuilderInterface::class);

        $subject = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
                $this->setFormKitConfigName('default');
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addTextPublic(FormBuilderInterface $b, string $name): void { $this->addText($b, $name); }
            public function addEmailPublic(FormBuilderInterface $b, string $name): void { $this->addEmail($b, $name); }
            public function addTextareaPublic(FormBuilderInterface $b, string $name): void { $this->addTextarea($b, $name); }
            public function addPasswordPublic(FormBuilderInterface $b, string $name): void { $this->addPassword($b, $name); }
            public function addUrlPublic(FormBuilderInterface $b, string $name): void { $this->addUrl($b, $name); }
            public function addIntegerPublic(FormBuilderInterface $b, string $name): void { $this->addInteger($b, $name); }
            public function addNumberPublic(FormBuilderInterface $b, string $name): void { $this->addNumber($b, $name); }
            public function addCheckboxPublic(FormBuilderInterface $b, string $name): void { $this->addCheckbox($b, $name); }
            public function addChoicePublic(FormBuilderInterface $b, string $name): void { $this->addChoice($b, $name); }
        };

        $calls = [];
        $builder->expects(self::exactly(9))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$calls, $builder) {
                self::assertIsArray($opts);
                $calls[] = [$name, $type];
                return $builder;
            });

        $subject->addTextPublic($builder, 'full_name');
        $subject->addEmailPublic($builder, 'email_address');
        $subject->addTextareaPublic($builder, 'message');
        $subject->addPasswordPublic($builder, 'password');
        $subject->addUrlPublic($builder, 'website');
        $subject->addIntegerPublic($builder, 'age');
        $subject->addNumberPublic($builder, 'price');
        $subject->addCheckboxPublic($builder, 'agree');
        $subject->addChoicePublic($builder, 'topic');

        self::assertSame([
            ['full_name', TextType::class],
            ['email_address', \Symfony\Component\Form\Extension\Core\Type\EmailType::class],
            ['message', TextareaType::class],
            ['password', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class],
            ['website', UrlType::class],
            ['age', IntegerType::class],
            ['price', NumberType::class],
            ['agree', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class],
            ['topic', ChoiceType::class],
        ], $calls);
    }

    public function testAddTranslationsUsesResolverMethodAndBuildsA2lixOptions(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'translations_demo';
            }

            /** @return array{default_locale: string, enabled_locales: array<int, string>} */
            protected function resolveFormKitTranslationsLocaleContext(array $options): array
            {
                return [
                    'default_locale' => 'es',
                    'enabled_locales' => ['es', 'en'],
                ];
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, [
                    'form_type' => 'App\\Form\\TranslationItemType',
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static function (array $opts): bool {
                    return $opts['form_type'] === 'App\\Form\\TranslationItemType'
                        && $opts['default_locale'] === 'es'
                        && $opts['enabled_locales'] === ['es', 'en']
                        && $opts['required_locales'] === ['es']
                        && ($opts['form_options']['row_attr']['class'] ?? '') === 'row'
                        && ($opts['form_options']['attr']['class'] ?? '') === 'row'
                        && array_key_exists('data_class', $opts)
                        && $opts['data_class'] === null;
                }),
            );

        $type->addTranslationsField($builder);
    }

    public function testAddTranslationsCanUseCallableLocaleResolver(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'translations_demo';
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, [
                    'form_type' => 'App\\Form\\TranslationItemType',
                    'required_locales' => ['fr'],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormKitTranslationsLocaleResolver(static function (array $options, object $subject): array {
            return [
                'default_locale' => 'fr',
                'enabled_locales' => ['fr', 'en'],
            ];
        });

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn(array $opts): bool => $opts['default_locale'] === 'fr'
                    && $opts['enabled_locales'] === ['fr', 'en']
                    && $opts['required_locales'] === ['fr']),
            );

        $type->addTranslationsField($builder);
    }

    public function testAddSelectAndChoiceVariantsMergeDefaults(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'choice_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addSelect($builder, 'country', ['choices' => ['es' => 'ES']]);
                $this->addMultiSelect($builder, 'hobbies', ['choices' => ['a' => 'A']]);
                $this->addChoiceRadios($builder, 'priority', ['choices' => ['n' => 'N']]);
                $this->addChoiceCheckboxes($builder, 'tags', ['choices' => ['x' => 'X']]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $calls = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(4))
            ->method('add')
            ->willReturnCallback(static function (string $name, string $fqcn, array $opts) use (&$calls, $builder) {
                $calls[] = [$name, $fqcn, $opts['expanded'] ?? null, $opts['multiple'] ?? null];

                return $builder;
            });

        $type->run($builder);

        self::assertSame([
            ['country', ChoiceType::class, false, false],
            ['hobbies', ChoiceType::class, false, true],
            ['priority', ChoiceType::class, true, false],
            ['tags', ChoiceType::class, true, true],
        ], $calls);
    }

    public function testAddMultiSelectSelectAllThrowsWhenBundleMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'choice_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addMultiSelectSelectAll($builder, 'roles', ['choices' => ['r' => 'R']]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(\LogicException::class);
        $type->run($builder);
    }

    public function testAddCKEditorFieldThrowsWhenBundleMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'ckeditor_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addCKEditorField($builder, 'body', []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(\LogicException::class);
        $type->run($builder);
    }

    public function testAddAutocompleteFieldDelegatesToAddWithDefaults(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'choice_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addAutocompleteField($builder, 'city', TextType::class, []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with('city', TextType::class, self::isType('array'));

        $type->run($builder);
    }
}
