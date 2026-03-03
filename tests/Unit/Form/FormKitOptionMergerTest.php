<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormKitOptionMerger;
use PHPUnit\Framework\TestCase;

final class FormKitOptionMergerTest extends TestCase
{
    public function testMergeAppliesCascadeAndAutoConventionKeys(): void
    {
        $merger = new FormKitOptionMerger(
            'forms',
            true,
            true,
            true,
            [
                'minimum'       => ['required' => false],
                'by_field_type' => ['text' => ['help' => 'type_help']],
                'by_form'       => ['contact_form' => ['label' => 'form_label']],
                'by_field'      => ['contact_form' => ['name' => ['help' => 'field_help']]],
            ],
            [
                'default' => ['form-control'],
                'by_type' => ['text' => ['text-specific']],
            ],
            [
                'default' => ['mb-3'],
                'by_type' => ['text' => ['row-text']],
            ],
        );

        $options = $merger->merge('contact_form', 'name', 'text');

        self::assertFalse($options['required']);
        self::assertSame('form_label', $options['label']);
        self::assertSame('field_help', $options['help']);
        self::assertSame('forms', $options['translation_domain']);
        self::assertSame('contact_form.name.placeholder', $options['attr']['placeholder']);
        self::assertSame('form-control text-specific', $options['attr']['class']);
        self::assertSame('mb-3 row-text', $options['row_attr']['class']);
    }

    public function testMergeRespectsExplicitOptionsAndCanDisableTranslationDomain(): void
    {
        $merger = new FormKitOptionMerger(
            null,
            true,
            true,
            true,
            [
                'minimum' => ['required' => true],
            ],
            [
                'default' => ['base-input'],
                'by_type' => ['text' => ['by-type']],
            ],
            [
                'default' => ['base-row'],
                'by_type' => [],
            ],
        );

        $options = $merger->merge('contact_form', 'email', 'text', [
            'label' => 'Email',
            'help'  => 'Explicit help',
            'attr'  => [
                'class'       => 'existing',
                'placeholder' => 'custom_placeholder',
            ],
            'row_attr' => [
                'class' => 'existing-row',
            ],
        ]);

        self::assertTrue($options['required']);
        self::assertSame('Email', $options['label']);
        self::assertSame('Explicit help', $options['help']);
        self::assertSame('custom_placeholder', $options['attr']['placeholder']);
        self::assertSame('existing base-input by-type', $options['attr']['class']);
        self::assertSame('existing-row base-row', $options['row_attr']['class']);
        self::assertArrayNotHasKey('translation_domain', $options);
    }
}
