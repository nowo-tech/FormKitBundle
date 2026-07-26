<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Type\StaticHtmlType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StaticHtmlTypeTest extends TestCase
{
    public function testConfigureOptionsDefaultsAndBlockPrefix(): void
    {
        $type     = new StaticHtmlType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([]);
        self::assertFalse($resolved['mapped']);
        self::assertFalse($resolved['label']);
        self::assertFalse($resolved['required']);
        self::assertSame('<div class="w-100"></div>', $resolved['html']);

        self::assertSame('static_html', $type->getBlockPrefix());
    }

    public function testBuildFormIsNoOpAndBuildViewExposesHtml(): void
    {
        $type    = new StaticHtmlType();
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())->method('add');

        $type->buildForm($builder, ['html' => '<hr />']);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, ['html' => '<hr />']);

        self::assertSame('<hr />', $view->vars['html']);
    }
}
