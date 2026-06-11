<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\FormFieldOptionsHelper;
use PHPUnit\Framework\TestCase;

final class FormFieldOptionsHelperTest extends TestCase
{
    public function testMergeSubFormDefaultsMergesUserOverDefaults(): void
    {
        $out = FormFieldOptionsHelper::mergeSubFormDefaults([
            'label' => 'Override',
            'attr'  => ['class' => 'row g-2'],
        ]);

        self::assertSame('Override', $out['label']);
        self::assertSame(['class' => 'row g-2'], $out['attr']);
        self::assertSame(['class' => 'col-12 col-md-12 col-xl-12'], $out['row_attr']);
    }

    public function testRemoveKeysUnsetsListedKeys(): void
    {
        $out = FormFieldOptionsHelper::removeKeys(
            ['a' => 1, 'placeholder' => 'x', 'b' => 2],
            ['placeholder'],
        );

        self::assertSame(['a' => 1, 'b' => 2], $out);
    }

    public function testStripPlaceholderFromMergedOptionsClearsRootAndAttr(): void
    {
        $out = FormFieldOptionsHelper::stripPlaceholderFromMergedOptions([
            'placeholder' => 'root',
            'attr'        => ['class' => 'x', 'placeholder' => 'in attr'],
        ]);

        self::assertArrayNotHasKey('placeholder', $out);
        self::assertSame(['class' => 'x'], $out['attr']);
    }
}
