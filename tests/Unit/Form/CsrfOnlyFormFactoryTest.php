<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidMethodOrTokenId(): iterable
    {
        yield 'GET' => ['GET', 'action'];
        yield 'get lowercase' => [' get ', 'action'];
        yield 'HEAD' => ['HEAD', 'action'];
        yield 'empty method' => ['  ', 'action'];
        yield 'OPTIONS' => ['OPTIONS', 'action'];
        yield 'TRACE' => ['trace', 'action'];
        yield 'unknown' => ['PURGE', 'action'];
        yield 'empty token id' => ['POST', ''];
        yield 'blank token id' => ['POST', "  \t"];
    }

    #[DataProvider('invalidMethodOrTokenId')]
    public function testCreateRejectsSafeMethodsAndEmptyTokenIds(string $method, string $tokenId): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::never())->method('create');

        $this->expectException(InvalidArgumentException::class);

        (new CsrfOnlyFormFactory($factory))->create('/x', $tokenId, $method);
    }

    #[DataProvider('invalidMethodOrTokenId')]
    public function testCreateNamedRejectsSafeMethodsAndEmptyTokenIds(string $method, string $tokenId): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::never())->method('createNamed');

        $this->expectException(InvalidArgumentException::class);

        (new CsrfOnlyFormFactory($factory))->createNamed('/x', $tokenId, $method);
    }

    #[DataProvider('invalidMethodOrTokenId')]
    public function testCreateWithFieldsRejectsSafeMethodsAndEmptyTokenIds(string $method, string $tokenId): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::never())->method('create');

        $this->expectException(InvalidArgumentException::class);

        (new CsrfOnlyFormFactory($factory))->createWithFields('/x', $tokenId, ['a' => '1'], $method);
    }
}
