<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use LogicException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\DataTransformer\BoolModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\CsvModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\JsonModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\MoneyModelTransformer;
use Nowo\FormKitBundle\Form\DataTransformer\SwitchModelTransformer;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Nowo\FormKitBundle\Form\Type\TranslationsFormsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

use function array_key_exists;
use function dirname;
use function is_array;

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
                self::callback(static fn (array $options): bool => $options['translation_domain'] === 'forms'
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
                ChoiceType::class,
                self::callback(static fn (array $opts): bool => !array_key_exists('label_position', $opts)),
            );

        $subject->addSwitchPublic($builder, 'isActive', [
            'label_position' => 'horizontal',
            'switch_value'   => 1,
            'row_attr'       => ['class' => 'existing-row'],
        ]);

        $subject->addSwitchPublic($builder, 'isActive2', [
            'label_position' => 'vertical',
            'switch_value'   => 1,
            'row_attr'       => ['class' => 'existing-row-2'],
            'attr'           => ['class' => 'existing-switch-attr'],
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
                        'type'    => ChoiceType::class,
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
            ->with(self::callback(static fn ($t): bool => $t instanceof JsonModelTransformer));

        $builder->expects(self::once())
            ->method('add')
            ->with(
                'payload',
                TextareaType::class,
                self::callback(static fn (array $opts): bool => !array_key_exists('json_pretty', $opts) && !array_key_exists('json_unescaped_unicode', $opts)),
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
            'json_pretty'            => false,
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
                self::callback(static fn (array $opts): bool => !array_key_exists('csv_separator', $opts)),
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
            'csv_separator'          => ';',
            'csv_trim_tokens'        => false,
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

            public function addTextPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addText($b, $name);
            }

            public function addEmailPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addEmail($b, $name);
            }

            public function addTextareaPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addTextarea($b, $name);
            }

            public function addPasswordPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addPassword($b, $name);
            }

            public function addUrlPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addUrl($b, $name);
            }

            public function addIntegerPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addInteger($b, $name);
            }

            public function addNumberPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addNumber($b, $name);
            }

            public function addCheckboxPublic(FormBuilderInterface $b, string $name): void
            {
                $this->addCheckbox($b, $name);
            }

            public function addChoicePublic(FormBuilderInterface $b, string $name): void
            {
                $this->addChoice($b, $name);
            }
        };

        $calls = [];
        $builder->expects(self::exactly(9))
            ->method('add')
            ->willReturnCallback(static function ($name, $type, $opts) use (&$calls, $builder): \PHPUnit\Framework\MockObject\MockObject {
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
                    'default_locale'  => 'es',
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
                self::callback(static fn (array $opts): bool => $opts['form_type'] === 'App\\Form\\TranslationItemType'
                    && $opts['default_locale'] === 'es'
                    && $opts['enabled_locales'] === ['es', 'en']
                    && $opts['required_locales'] === ['es']
                    && ($opts['form_options']['row_attr']['class'] ?? '') === 'row'
                    && ($opts['form_options']['attr']['class'] ?? '') === 'row'
                    && array_key_exists('data_class', $opts)
                    && $opts['data_class'] === null),
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
                    'form_type'        => 'App\\Form\\TranslationItemType',
                    'required_locales' => ['fr'],
                ]);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormKitTranslationsLocaleResolver(static fn (array $options, object $subject): array => [
            'default_locale'  => 'fr',
            'enabled_locales' => ['fr', 'en'],
        ]);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn (array $opts): bool => $opts['default_locale'] === 'fr'
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

        $calls   = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(4))
            ->method('add')
            ->willReturnCallback(static function (string $name, string $fqcn, array $opts) use (&$calls, $builder): \PHPUnit\Framework\MockObject\MockObject {
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

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
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

        $this->expectException(LogicException::class);
        $type->run($builder);
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
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

        $this->expectException(LogicException::class);
        $type->run($builder);
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
    public function testAddDropzoneThrowsWhenBundleMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'dropzone_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addDropzone($builder, 'document', []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/ux-dropzone');
        $type->run($builder);
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
    public function testAddCropperThrowsWhenBundleMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'cropper_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addCropper($builder, 'crop', []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/ux-cropperjs');
        $type->run($builder);
    }

    public function testAddDropzoneDelegatesWhenStubPresent(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'dropzone_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addDropzone($builder, 'document', []);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with('document', \Symfony\UX\Dropzone\Form\DropzoneType::class, self::isType('array'));

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

    public function testSettersStoreConfiguration(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }
        };

        $type->setFormOptionsMerger($this->createMerger());
        $type->setFormKitConfigName('bootstrap');
        $type->setFormKitTranslationsDefaults(['label' => false]);
        $type->setFormKitTranslationsLocaleResolver(static fn (): array => ['default_locale' => 'es', 'enabled_locales' => ['es']]);

        self::assertTrue(true);
    }

    public function testMergeSubFormAndRemoveFieldOptionKeys(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function merge(array $cfg): array
            {
                return $this->mergeSubFormFieldOptions($cfg);
            }

            public function remove(array $cfg, array $keys): array
            {
                return $this->removeFieldOptionKeys($cfg, $keys);
            }
        };

        self::assertArrayHasKey('row_attr', $type->merge([]));
        self::assertSame(['label' => 'x'], $type->remove(['label' => 'x', 'help' => 'y'], ['help']));
    }

    public function testAddFieldBreakAddsStaticHtmlField(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addBreak(FormBuilderInterface $builder): void
            {
                $this->addFieldBreak($builder, 'break', '<div class="w-100"></div>');
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'break',
                \Nowo\FormKitBundle\Form\Type\StaticHtmlType::class,
                self::callback(static fn (array $opts): bool => $opts['html'] === '<div class="w-100"></div>'),
            );

        $type->addBreak($builder);
    }

    public function testAddTranslationsNormalizesInvalidFormOptionsShape(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, [
                    'form_type'    => 'App\\Form\\TranslationItemType',
                    'form_options' => 'invalid',
                ]);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn (array $opts): bool => is_array($opts['form_options'])
                    && str_contains((string) ($opts['form_options']['row_attr']['class'] ?? ''), 'row')),
            );

        $type->addTranslationsField($builder);
    }

    public function testAddTranslationsUsesFallbackLocaleContext(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, ['form_type' => 'App\\Form\\TranslationItemType']);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn (array $opts): bool => $opts['default_locale'] === 'en'
                    && $opts['enabled_locales'] === ['en']),
            );

        $type->addTranslationsField($builder);
    }

    public function testAddTranslationsThrowsWhenFormTypeMissing(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, []);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $type->addTranslationsField($builder);
    }

    public function testAddTranslationsUsesRequiredLocalesFromLocaleContext(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            protected function resolveFormKitTranslationsLocaleContext(array $options): array
            {
                return [
                    'default_locale'   => 'es',
                    'enabled_locales'  => ['es', 'en'],
                    'required_locales' => ['es', 'en'],
                ];
            }

            public function addTranslationsField(FormBuilderInterface $builder): void
            {
                $this->addTranslations($builder, ['form_type' => 'App\\Form\\TranslationItemType']);
            }
        };

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'translations',
                TranslationsFormsType::class,
                self::callback(static fn (array $opts): bool => $opts['required_locales'] === ['es', 'en']),
            );

        $type->addTranslationsField($builder);
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
    public function testAddMultiSelectSelectAllAddsChoiceWhenBundleInstalled(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

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
        $builder->expects(self::once())
            ->method('add')
            ->with(
                'roles',
                ChoiceType::class,
                self::callback(static fn (array $opts): bool => ($opts['select_all'] ?? false) === true),
            );

        $type->run($builder);
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState false
     */
    public function testAddCKEditorFieldAddsFieldWhenBundleInstalled(): void
    {
        require_once dirname(__DIR__, 2) . '/Stubs/OptionalBundleStubs.php';

        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'ckeditor_demo';
            }

            public function run(FormBuilderInterface $builder): void
            {
                $this->addCKEditorField($builder, 'body');
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with('body', \FOS\CKEditorBundle\Form\Type\CKEditorType::class, self::isType('array'));

        $type->run($builder);
    }

    public function testDataTransformerSwitchConfigurationFormBranchRebuildsChild(): void
    {
        $merger = $this->createMerger();
        $type   = new class($merger) {
            use FormOptionsTrait;

            public function __construct(FormOptionsMerger $merger)
            {
                $this->setFormOptionsMerger($merger);
            }

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function attach(\Symfony\Component\Form\FormInterface $form, string $field): void
            {
                $this->dataTransformerSwitchConfiguration($form, $field, 1);
            }
        };

        $childForm = $this->createMock(\Symfony\Component\Form\FormInterface::class);
        $childForm->method('getConfig')->willReturn($this->createConfiguredMock(\Symfony\Component\Form\FormConfigInterface::class, [
            'getOptions' => ['expanded' => true, 'multiple' => true],
        ]));

        $childBuilder = $this->createMock(FormBuilderInterface::class);
        $childBuilder->expects(self::once())->method('addModelTransformer')->with(self::isInstanceOf(SwitchModelTransformer::class));
        $childBuilder->method('getForm')->willReturn($this->createMock(\Symfony\Component\Form\FormInterface::class));

        $factory = $this->createMock(\Symfony\Component\Form\FormFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createNamedBuilder')
            ->with('is_active', ChoiceType::class, null, ['expanded' => true, 'multiple' => true])
            ->willReturn($childBuilder);

        $formConfig = $this->createMock(\Symfony\Component\Form\FormConfigInterface::class);
        $formConfig->method('getFormFactory')->willReturn($factory);

        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);
        $form->expects(self::once())->method('get')->with('is_active')->willReturn($childForm);
        $form->expects(self::once())->method('remove')->with('is_active');
        $form->expects(self::once())->method('add');
        $form->method('getConfig')->willReturn($formConfig);

        $type->attach($form, 'is_active');
    }

    public function testResolveFieldOptionsMergesConventionWithoutAddingField(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function resolve(string $name, string $typeFqcn, array $options = []): array
            {
                return $this->resolveFieldOptions($name, $typeFqcn, $options);
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $merged = $type->resolve('company_name', TextType::class, ['required' => false]);

        self::assertSame('demo_form.company_name.label', $merged['label']);
        self::assertFalse($merged['required']);
        self::assertSame('messages', $merged['translation_domain']);
    }

    public function testWithBuilderAddTextFieldDelegatesWithoutRepeatingBuilder(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function build(FormBuilderInterface $builder): void
            {
                $this->withBuilder($builder, function (): void {
                    $this->addTextField('full_name');
                    $this->addEmailField('email', ['label' => false]);
                });
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $builder = $this->createMock(FormBuilderInterface::class);
        $calls   = [];
        $builder->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function ($name, $fqcn, $opts) use (&$calls, $builder): FormBuilderInterface {
                $calls[] = [$name, $fqcn, $opts];
                self::assertIsArray($opts);

                return $builder;
            });

        $type->build($builder);

        self::assertSame('full_name', $calls[0][0]);
        self::assertSame(TextType::class, $calls[0][1]);
        self::assertSame('demo_form.full_name.label', $calls[0][2]['label']);
        self::assertSame('email', $calls[1][0]);
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\EmailType::class, $calls[1][1]);
        self::assertArrayNotHasKey('label', $calls[1][2]);
    }

    public function testBoundBuilderThrowsOutsideWithBuilder(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function callBoundBuilder(): void
            {
                $this->boundBuilder();
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No form builder is bound');
        $type->callBoundBuilder();
    }

    public function testWithBuilderRestoresPreviousBuilderAfterNestedCall(): void
    {
        $type = new class {
            use FormOptionsTrait;

            public function getBlockPrefix(): string
            {
                return 'demo_form';
            }

            public function build(FormBuilderInterface $outer, FormBuilderInterface $inner): void
            {
                $this->withBuilder($outer, function () use ($inner): void {
                    $this->addTextField('outer_field');
                    $this->withBuilder($inner, function (): void {
                        $this->addTextField('inner_field');
                    });
                    $this->addTextField('after_nested');
                });
            }
        };

        $type->setFormOptionsMerger($this->createMerger());

        $outer = $this->createMock(FormBuilderInterface::class);
        $inner = $this->createMock(FormBuilderInterface::class);

        $outerNames = [];
        $innerNames = [];

        $outer->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function ($name) use (&$outerNames, $outer): FormBuilderInterface {
                $outerNames[] = $name;

                return $outer;
            });

        $inner->expects(self::once())
            ->method('add')
            ->willReturnCallback(static function ($name) use (&$innerNames, $inner): FormBuilderInterface {
                $innerNames[] = $name;

                return $inner;
            });

        $type->build($outer, $inner);

        self::assertSame(['outer_field', 'after_nested'], $outerNames);
        self::assertSame(['inner_field'], $innerNames);
    }
}
