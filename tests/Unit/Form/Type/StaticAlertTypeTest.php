<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Type;

use Nowo\FormKitBundle\Form\Type\StaticAlertType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class StaticAlertTypeTest extends TestCase
{
    public function testConfigureOptionsAndBuildView(): void
    {
        $type = new StaticAlertType();
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $resolved = $resolver->resolve([
            'message' => 'Hello',
        ]);

        self::assertFalse($resolved['mapped']);
        self::assertFalse($resolved['label']);
        self::assertFalse($resolved['required']);
        self::assertSame('info', $resolved['alert_type']);
        self::assertArrayHasKey('translation_domain', $resolved);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $type->buildView($view, $form, [
            'message' => 'Hello',
            'alert_type' => 'warning',
            'translation_domain' => 'messages',
        ]);

        self::assertSame('Hello', $view->vars['message']);
        self::assertSame('warning', $view->vars['alert_type']);
        self::assertSame('messages', $view->vars['translation_domain']);

        self::assertSame('static_alert', $type->getBlockPrefix());
    }
}

