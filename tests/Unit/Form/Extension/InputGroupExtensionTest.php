<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Extension;

use Nowo\FormKitBundle\Form\Extension\InputGroupExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InputGroupExtensionTest extends TestCase
{
    public function testBuildViewAddsVarsAndConfigureOptionsDefaults(): void
    {
        $ext = new InputGroupExtension();

        self::assertSame([FormType::class], iterator_to_array($ext::getExtendedTypes()));

        $resolver = new OptionsResolver();
        $ext->configureOptions($resolver);
        $resolved = $resolver->resolve([]);
        self::assertArrayHasKey('input_group_prefix', $resolved);
        self::assertArrayHasKey('input_group_suffix', $resolved);
        self::assertNull($resolved['input_group_prefix']);
        self::assertNull($resolved['input_group_suffix']);

        $view  = new FormView();
        $form  = $this->createMock(FormInterface::class);
        $ext->buildView($view, $form, [
            'input_group_prefix' => '@',
            'input_group_suffix' => '#',
        ]);

        self::assertSame('@', $view->vars['input_group_prefix']);
        self::assertSame('#', $view->vars['input_group_suffix']);
    }
}

