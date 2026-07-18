<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

final class FormOptionsMergerTest extends TestCase
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
                        'text' => [
                            'constraints' => ['NotBlank'],
                        ],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );
    }

    public function testResolveThrowsForUnknownConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown form kit config "missing".');

        $this->createMerger()->resolve('demo_form', 'name', 'text', [], 'missing');
    }

    public function testResolveBuildsConstraintsFromFieldTypeDefaults(): void
    {
        $merged = $this->createMerger()->resolve('demo_form', 'name', 'text');

        self::assertArrayHasKey('constraints', $merged);
        self::assertInstanceOf(NotBlank::class, $merged['constraints'][0]);
    }

    public function testResolveAppliesPlaceholderFromMergedOptionsWhenAttrHasNoPlaceholder(): void
    {
        $merged = $this->createMerger()->resolve('demo_form', 'name', 'text', [
            'placeholder' => 'demo_form.name.placeholder',
        ]);

        self::assertSame('demo_form.name.placeholder', $merged['attr']['placeholder']);
    }

    public function testResolveRemovesPlaceholderWhenExplicitFalse(): void
    {
        $merged = $this->createMerger()->resolve('demo_form', 'name', 'text', [
            'placeholder' => false,
        ]);

        self::assertArrayNotHasKey('placeholder', $merged['attr'] ?? []);
    }

    public function testResolveRemovesLabelAndHelpWhenExplicitFalse(): void
    {
        $merged = $this->createMerger()->resolve('demo_form', 'name', 'text', [
            'label' => false,
            'help'  => false,
        ]);

        self::assertArrayNotHasKey('label', $merged);
        self::assertArrayNotHasKey('help', $merged);
    }

    public function testResolveDoesNotOverwriteExistingAttrPlaceholder(): void
    {
        $merged = $this->createMerger()->resolve('demo_form', 'name', 'text', [
            'attr' => ['placeholder' => 'existing'],
        ]);

        self::assertSame('existing', $merged['attr']['placeholder']);
    }

    public function testResolveAppliesByFormDefaultsAndFieldOverrides(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => [
                        'attr'     => ['class' => 'form-control'],
                        'row_attr' => ['class' => 'mb-3'],
                    ],
                    'field_types' => [],
                    'by_form'     => [
                        'contact' => [
                            'defaults' => [
                                'attr'     => ['class' => 'form-control-lg'],
                                'row_attr' => ['class' => 'col-12 mb-4'],
                            ],
                            'fields' => [
                                'email' => [
                                    'attr' => ['autocomplete' => 'email'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        $merged = $merger->resolve('contact', 'email', 'email');

        self::assertSame('form-control-lg', $merged['attr']['class']);
        self::assertSame('col-12 mb-4', $merged['row_attr']['class']);
        self::assertSame('email', $merged['attr']['autocomplete']);
    }

    public function testResolveAppliesConstraintMessageConventionWhenEnabled(): void
    {
        $merger = new FormOptionsMerger(
            [
                'default' => [
                    'translation_domain' => 'messages',
                    'defaults'           => ['attr' => [], 'row_attr' => []],
                    'field_types'        => [
                        'text' => ['constraints' => ['NotBlank']],
                    ],
                    'constraint_message_convention' => true,
                    'by_form'                       => [],
                ],
            ],
            'default',
            new ConstraintDefinitionFactory(),
        );

        $merged = $merger->resolve('user_profile', 'fullName', 'text');

        self::assertInstanceOf(NotBlank::class, $merged['constraints'][0]);
        self::assertSame('user_profile.full_name.constraints.NotBlank', $merged['constraints'][0]->message);
    }
}
