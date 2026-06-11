<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form\Constraint;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ConstraintDefinitionFactoryTest extends TestCase
{
    public function testCreatesFromShortNames(): void
    {
        $f = new ConstraintDefinitionFactory();
        $c = $f->create(['NotBlank', 'Email']);

        self::assertCount(2, $c);
        self::assertInstanceOf(NotBlank::class, $c[0]);
        self::assertInstanceOf(Email::class, $c[1]);
    }

    public function testCreatesFromSingleKeyMaps(): void
    {
        $f = new ConstraintDefinitionFactory();
        $c = $f->create([
            ['NotBlank' => ['message' => 'custom']],
            ['Email' => ['mode' => Email::VALIDATION_MODE_HTML5]],
        ]);

        self::assertSame('custom', $c[0]->message);
        self::assertSame(Email::VALIDATION_MODE_HTML5, $c[1]->mode);
    }

    public function testCreatesFromTypeAndOptions(): void
    {
        $f = new ConstraintDefinitionFactory();
        $c = $f->create([
            ['type' => 'Length', 'options' => ['max' => 10]],
        ]);

        self::assertInstanceOf(Length::class, $c[0]);
        self::assertSame(10, $c[0]->max);
    }

    public function testRejectsFqcnOutsideValidatorPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create(['stdClass']);
    }
}
