<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\RequiredLabelSuffixExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class RequiredLabelSuffixExtensionTest extends TestCase
{
    public function testBuildViewUsesDefaultConfigSuffix(): void
    {
        $ext = new RequiredLabelSuffixExtension(
            configs: [
                'default' => ['required_label_suffix' => ' *'],
            ],
            defaultConfigName: 'default',
        );

        self::assertSame([FormType::class], iterator_to_array($ext::getExtendedTypes()));

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, []);
        self::assertSame(' *', $view->vars['required_label_suffix']);
    }

    public function testBuildViewSetsNullWhenConfigMissing(): void
    {
        $ext = new RequiredLabelSuffixExtension(
            configs: [],
            defaultConfigName: 'default',
        );

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $ext->buildView($view, $form, []);
        self::assertArrayHasKey('required_label_suffix', $view->vars);
        self::assertNull($view->vars['required_label_suffix']);
    }
}
