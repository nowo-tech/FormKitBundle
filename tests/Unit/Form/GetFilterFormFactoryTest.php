<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\GetFilterFormFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class GetFilterFormFactoryTest extends TestCase
{
    public function testCreateUsesEmptyFormName(): void
    {
        $form    = $this->createMock(FormInterface::class);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createNamed')
            ->with('', FormType::class, ['q' => 'beacon'], ['method' => 'GET'])
            ->willReturn($form);

        $subject = new GetFilterFormFactory($factory);

        self::assertSame(
            $form,
            $subject->create(FormType::class, ['q' => 'beacon'], ['method' => 'GET']),
        );
    }
}
