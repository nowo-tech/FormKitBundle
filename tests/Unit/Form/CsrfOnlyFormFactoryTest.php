<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class CsrfOnlyFormFactoryTest extends TestCase
{
    public function testCreateBuildsUnnamedCsrfOnlyType(): void
    {
        $form    = $this->createMock(FormInterface::class);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::once())
            ->method('create')
            ->with(
                CsrfOnlyType::class,
                null,
                [
                    'action'          => '/toggle',
                    'method'          => 'POST',
                    'csrf_token_id'   => 'project_toggle',
                    'csrf_field_name' => '_token',
                ],
            )
            ->willReturn($form);

        $subject = new CsrfOnlyFormFactory($factory);

        self::assertSame($form, $subject->create('/toggle', 'project_toggle'));
    }

    public function testCreateNamedUsesCsrfOnlyFormName(): void
    {
        $form    = $this->createMock(FormInterface::class);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createNamed')
            ->with(
                'csrf_only',
                CsrfOnlyType::class,
                null,
                [
                    'action'          => '/delete',
                    'method'          => 'DELETE',
                    'csrf_token_id'   => 'project_delete',
                    'csrf_field_name' => '_csrf_token',
                ],
            )
            ->willReturn($form);

        $subject = new CsrfOnlyFormFactory($factory);

        self::assertSame(
            $form,
            $subject->createNamed('/delete', 'project_delete', 'delete', '_csrf_token'),
        );
    }

    public function testCreateWithFieldsBuildsHiddenFieldsCsrfType(): void
    {
        $form    = $this->createMock(FormInterface::class);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::once())
            ->method('create')
            ->with(
                HiddenFieldsCsrfType::class,
                [
                    'enabled'  => '1',
                    'redirect' => '',
                ],
                [
                    'action'          => '/save',
                    'method'          => 'POST',
                    'csrf_token_id'   => 'save_settings',
                    'csrf_field_name' => '_token',
                    'fields'          => ['enabled', 'redirect'],
                    'field_types'     => ['enabled' => 'checkbox'],
                    'field_options'   => ['enabled' => ['required' => false]],
                ],
            )
            ->willReturn($form);

        $subject = new CsrfOnlyFormFactory($factory);

        self::assertSame(
            $form,
            $subject->createWithFields(
                '/save',
                'save_settings',
                ['enabled' => 1, 'redirect' => null],
                'POST',
                '_token',
                ['enabled' => 'checkbox'],
                ['enabled' => ['required' => false]],
            ),
        );
    }
}
