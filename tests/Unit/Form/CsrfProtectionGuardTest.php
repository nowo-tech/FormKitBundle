<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Form;

use Closure;
use LogicException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\CsrfProtectionGuard;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Regression for the CSRF fail-open: without the form CSRF extension the action forms used to submit as valid.
 */
#[CoversClass(CsrfProtectionGuard::class)]
#[CoversClass(CsrfOnlyType::class)]
#[CoversClass(HiddenFieldsCsrfType::class)]
final class CsrfProtectionGuardTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FormFactoryInterface): FormInterface<mixed>}>
     */
    public static function formProvider(): iterable
    {
        yield 'CsrfOnlyType direct' => [static fn (FormFactoryInterface $f): FormInterface => $f->create(CsrfOnlyType::class, null, ['csrf_token_id' => 'delete_1'])];
        yield 'HiddenFieldsCsrfType direct' => [static fn (FormFactoryInterface $f): FormInterface => $f->create(HiddenFieldsCsrfType::class, ['id' => '1'], ['csrf_token_id' => 'toggle_1', 'fields' => ['id']])];
        yield 'factory create' => [static fn (FormFactoryInterface $f): FormInterface => (new CsrfOnlyFormFactory($f))->create('/delete/1', 'delete_1')];
        yield 'factory createNamed' => [static fn (FormFactoryInterface $f): FormInterface => (new CsrfOnlyFormFactory($f))->createNamed('/delete/1', 'delete_1')];
        yield 'factory createWithFields' => [static fn (FormFactoryInterface $f): FormInterface => (new CsrfOnlyFormFactory($f))->createWithFields('/toggle/1', 'toggle_1', ['enabled' => '1'])];
    }

    /**
     * @param Closure(FormFactoryInterface): FormInterface<mixed> $create
     */
    #[DataProvider('formProvider')]
    public function testFailsClosedWithoutTheFormCsrfExtension(Closure $create): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('requires Symfony form CSRF protection');

        $form = $create($this->factory(withCsrf: false));
        // Old behaviour: the POST below was accepted as valid without any token.
        $form->submit([]);
    }

    /**
     * @param Closure(FormFactoryInterface): FormInterface<mixed> $create
     */
    #[DataProvider('formProvider')]
    public function testRejectsAMissingTokenWithTheExtension(Closure $create): void
    {
        $form = $create($this->factory(withCsrf: true));
        $form->submit([]);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
    }

    public function testExplicitOptOutIsLeftToTheCaller(): void
    {
        $form = $this->factory(withCsrf: false)->create(CsrfOnlyType::class, null, ['csrf_protection' => false]);
        $form->submit([]);

        self::assertTrue($form->isValid());
    }

    public function testAssertAvailableAcceptsATokenManager(): void
    {
        CsrfProtectionGuard::assertAvailable(['csrf_protection' => true, 'csrf_token_manager' => $this->createStub(CsrfTokenManagerInterface::class)], CsrfOnlyType::class);

        $this->addToAssertionCount(1);
    }

    public function testAssertAvailableTreatsMissingOptionsAsProtected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(CsrfOnlyType::class);

        CsrfProtectionGuard::assertAvailable([], CsrfOnlyType::class);
    }

    private function factory(bool $withCsrf): FormFactoryInterface
    {
        $typeMap = new FormTypeMap();
        $merger  = new FormOptionsMerger(
            ['default' => ['translation_domain' => 'messages', 'auto_placeholder' => false, 'auto_help' => false, 'defaults' => ['attr' => [], 'row_attr' => [], 'help_attr' => []], 'field_types' => []]],
            'default',
            new ConstraintDefinitionFactory(),
            null,
            $typeMap,
        );

        $builder = Forms::createFormFactoryBuilder()
            ->addTypes([new CsrfOnlyType($merger, $typeMap), new HiddenFieldsCsrfType($merger, $typeMap)]);

        if ($withCsrf) {
            $tokenManager = $this->createStub(CsrfTokenManagerInterface::class);
            $tokenManager->method('getToken')->willReturnCallback(static fn (string $id): CsrfToken => new CsrfToken($id, 'token:' . $id));
            $tokenManager->method('isTokenValid')->willReturnCallback(static fn (CsrfToken $token): bool => $token->getValue() === 'token:' . $token->getId());
            $builder->addExtension(new CsrfExtension($tokenManager));
        }

        return $builder->getFormFactory();
    }
}
