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

        self::assertInstanceOf(NotBlank::class, $c[0]);
        self::assertInstanceOf(Email::class, $c[1]);
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

    public function testPassesThroughConstraintInstances(): void
    {
        $constraint = new NotBlank(message: 'required');
        $created    = (new ConstraintDefinitionFactory())->create([$constraint]);

        self::assertSame($constraint, $created[0]);
    }

    public function testRejectsInvalidDefinitionType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create([123]);
    }

    public function testRejectsInvalidMultiKeyDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create([['NotBlank' => [], 'Email' => []]]);
    }

    public function testRejectsUnknownConstraintShortName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create(['DoesNotExistConstraint']);
    }

    public function testRejectsFqcnOutsideValidatorPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create(['App\\Validator\\NotAConstraint']);
    }

    public function testCreatesFromValidatorFqcn(): void
    {
        $created = (new ConstraintDefinitionFactory())->create([
            NotBlank::class,
        ]);

        self::assertInstanceOf(NotBlank::class, $created[0]);
    }

    public function testAppliesMessageKeyPrefixWhenMessageMissing(): void
    {
        $created = (new ConstraintDefinitionFactory())->create(
            ['NotBlank', ['Length' => ['max' => 10]]],
            'user_profile.email',
        );

        self::assertInstanceOf(NotBlank::class, $created[0]);
        self::assertInstanceOf(Length::class, $created[1]);
        self::assertSame('user_profile.email.constraints.NotBlank', $created[0]->message);
        self::assertSame('user_profile.email.constraints.Length.max', $created[1]->maxMessage);
        self::assertSame(10, $created[1]->max);
    }

    public function testAppliesMinMessageConventionForLengthConstraints(): void
    {
        $created = (new ConstraintDefinitionFactory())->create(
            [['Length' => ['min' => 3]]],
            'user_profile.password',
        );

        self::assertInstanceOf(Length::class, $created[0]);
        self::assertSame(3, $created[0]->min);
        self::assertSame('user_profile.password.constraints.Length.min', $created[0]->minMessage);
    }

    public function testDoesNotOverrideExplicitMessageWhenPrefixSet(): void
    {
        $created = (new ConstraintDefinitionFactory())->create(
            [['NotBlank' => ['message' => 'keep_me']]],
            'user_profile.email',
        );

        self::assertInstanceOf(NotBlank::class, $created[0]);
        self::assertSame('keep_me', $created[0]->message);
    }

    public function testRejectsMissingValidatorFqcn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create([
            'Symfony\\Component\\Validator\\Constraints\\TotallyMissingConstraint',
        ]);
    }

    public function testMessageConventionSkipsInjectionWhenConstraintHasNoConstructor(): void
    {
        if (!class_exists('Symfony\\Component\\Validator\\Constraints\\NoCtorCoverageConstraint')) {
            eval('namespace Symfony\\Component\\Validator\\Constraints; final class NoCtorCoverageConstraint extends \\Symfony\\Component\\Validator\\Constraint {}');
        }

        $created = (new ConstraintDefinitionFactory())->create([
            'Symfony\\Component\\Validator\\Constraints\\NoCtorCoverageConstraint',
        ], 'user_profile.email');

        self::assertCount(1, $created);
        self::assertSame(
            'Symfony\\Component\\Validator\\Constraints\\NoCtorCoverageConstraint',
            $created[0]::class,
        );
    }

    public function testRejectsValidatorFqcnThatDoesNotExtendConstraint(): void
    {
        if (!class_exists('Symfony\\Component\\Validator\\Constraints\\FakeCoverageConstraintLikeClass')) {
            eval('namespace Symfony\\Component\\Validator\\Constraints; final class FakeCoverageConstraintLikeClass {}');
        }

        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create([
            'Symfony\\Component\\Validator\\Constraints\\FakeCoverageConstraintLikeClass',
        ]);
    }

    public function testRejectsValidatorShortNameWhenResolvedClassDoesNotExtendConstraint(): void
    {
        if (!class_exists('Symfony\\Component\\Validator\\Constraints\\FakeCoverageConstraintLikeClass')) {
            eval('namespace Symfony\\Component\\Validator\\Constraints; final class FakeCoverageConstraintLikeClass {}');
        }

        $this->expectException(InvalidArgumentException::class);
        (new ConstraintDefinitionFactory())->create([
            'FakeCoverageConstraintLikeClass',
        ]);
    }
}
