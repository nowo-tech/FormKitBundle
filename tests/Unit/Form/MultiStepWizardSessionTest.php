<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Nowo\FormKitBundle\Form\MultiStepWizardSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class MultiStepWizardSessionTest extends TestCase
{
    private function createSession(array $bag): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(static function (string $key, mixed $default) use ($bag) {
            return $bag;
        });

        return $session;
    }

    public function testWizardSessionComputesCurrentStepAndCollectedData(): void
    {
        $steps = [
            'contact' => ['label' => 'Contact', 'fields' => ['name' => 'text']],
            'confirm' => ['label' => 'Confirm', 'fields' => []],
        ];

        $bag = [
            'index' => 0,
            'data'  => [
                'contact' => ['name' => 'Ada'],
                'confirm' => ['agree' => true],
            ],
        ];

        $session = $this->createSession($bag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $subject = new MultiStepWizardSession($steps, 'wiz', $requestStack);

        self::assertSame(['contact', 'confirm'], $subject->getStepKeys());
        self::assertSame('contact', $subject->getCurrentStepKey());
        self::assertSame(0, $subject->getCurrentIndex());
        self::assertSame($bag['data'], $subject->getCollectedData());
        self::assertSame(['name' => 'Ada', 'agree' => true], $subject->getCollectedDataFlat());
        self::assertSame('Contact', $subject->getStepLabel('contact'));
        self::assertSame([], $subject->getStepFields('confirm'));
        self::assertSame($steps, $subject->getSteps());
    }

    public function testCurrentStepKeyFallsBackToLastWhenIndexIsOutOfBounds(): void
    {
        $steps = [
            'a' => ['label' => 'A', 'fields' => []],
            'b' => ['label' => 'B', 'fields' => []],
        ];

        $bag = [
            'index' => 10,
            'data'  => [],
        ];

        $session = $this->createSession($bag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $subject = new MultiStepWizardSession($steps, 'wiz', $requestStack);

        self::assertSame('b', $subject->getCurrentStepKey());
    }

    public function testAdvanceAndResetAndSetStepDataUpdateSession(): void
    {
        $steps = [
            's1' => ['label' => 'S1', 'fields' => []],
            's2' => ['label' => 'S2', 'fields' => []],
        ];

        $bag = ['index' => 0, 'data' => ['s1' => ['x' => 1]]];

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(static function (string $key, mixed $default) use (&$bag) {
            return $bag;
        });
        $session->expects(self::exactly(3))
            ->method('set')
            ->willReturnCallback(static function (string $key, mixed $value) use (&$bag) {
                // Symfony session bag is an array.
                $bag = $value;
            });

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $subject = new MultiStepWizardSession($steps, 'wiz', $requestStack);

        $subject->setStepData('s2', ['y' => 2]);
        self::assertSame(['y' => 2], $bag['data']['s2']);

        $subject->advance();
        self::assertSame(1, $bag['index']);

        $subject->reset();
        self::assertSame(0, $bag['index']);
        self::assertSame([], $bag['data']);
    }

    public function testIsCompleteWhenIndexIsGreaterOrEqualToStepCount(): void
    {
        $steps = [
            's1' => ['label' => 'S1', 'fields' => []],
            's2' => ['label' => 'S2', 'fields' => []],
        ];

        $bag     = ['index' => 2, 'data' => []];
        $session = $this->createSession($bag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $subject = new MultiStepWizardSession($steps, 'wiz', $requestStack);
        self::assertTrue($subject->isComplete());
    }
}
