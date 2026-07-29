<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\MultiStepWizardSessionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class MultiStepWizardSessionFactoryTest extends TestCase
{
    public function testCreateReturnsMultiStepWizardSession(): void
    {
        $requestStack = $this->createMock(RequestStack::class);

        $factory = new MultiStepWizardSessionFactory($requestStack);

        $steps = [
            's1' => ['label' => 'S1', 'fields' => []],
            's2' => ['label' => 'S2', 'fields' => []],
        ];

        $session = $factory->create($steps, 'wiz');
        self::assertSame(['s1', 's2'], $session->getStepKeys());
    }
}
