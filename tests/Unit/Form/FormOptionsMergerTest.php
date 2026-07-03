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
}
