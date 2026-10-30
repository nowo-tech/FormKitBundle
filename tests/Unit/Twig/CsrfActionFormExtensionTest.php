<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use InvalidArgumentException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormTypeMap;
use Nowo\FormKitBundle\Form\Type\CsrfOnlyType;
use Nowo\FormKitBundle\Form\Type\HiddenFieldsCsrfType;
use Nowo\FormKitBundle\Twig\CsrfActionFormExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;

final class CsrfActionFormExtensionTest extends TestCase
{
    public function testRegistersSingleCsrfActionFormFunction(): void
    {
        $functions = $this->extension()->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('csrf_action_form', $functions[0]->getName());
        self::assertSame(CsrfActionFormExtension::FUNCTION_NAME, $functions[0]->getName());
    }

    public function testNamedFormNestsTokenUnderCsrfOnly(): void
    {
        $view = $this->extension()->csrfActionForm('/delete/1', 'item_delete');

        self::assertSame('csrf_only', $view->vars['full_name']);
        self::assertSame('/delete/1', $view->vars['action']);
        self::assertSame('POST', $view->vars['method']);
        self::assertSame('csrf_only[_token]', $view->children['_token']->vars['full_name']);
        self::assertSame('token:item_delete', $view->children['_token']->vars['value']);
    }

    public function testFlatFormWithCustomCsrfFieldName(): void
    {
        $view = $this->extension()->csrfActionForm('/kit/save', 'kit_save', named: false, csrfFieldName: '_csrf_token');

        self::assertSame('', $view->vars['full_name']);
        self::assertArrayNotHasKey('_token', $view->children);
        self::assertSame('_csrf_token', $view->children['_csrf_token']->vars['full_name']);
        self::assertSame('token:kit_save', $view->children['_csrf_token']->vars['value']);
    }

    public function testMethodIsNormalised(): void
    {
        $view = $this->extension()->csrfActionForm('/item/1', 'item_patch', ' patch ');

        self::assertSame('PATCH', $view->vars['method']);
    }

    /**
     * @return iterable<string, array{string, string, bool, array<string, scalar|null>}>
     */
    public static function rejectedCalls(): iterable
    {
        yield 'GET named' => ['get', 'search', true, []];
        yield 'HEAD flat' => ['HEAD', 'search', false, []];
        yield 'GET with fields' => ['GET', 'search', true, ['q' => 'x']];
        yield 'empty token id' => ['POST', '', true, []];
        yield 'blank token id' => ['POST', '  ', false, []];
        yield 'empty id + fields' => ['POST', '', true, ['q' => 'x']];
    }

    /**
     * @param array<string, scalar|null> $fields
     */
    #[DataProvider('rejectedCalls')]
    public function testRejectsSafeMethodsAndEmptyTokenIds(string $method, string $tokenId, bool $named, array $fields): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->extension()->csrfActionForm('/x', $tokenId, $method, $named, fields: $fields);
    }

    public function testTwigCallWithGetFailsClosed(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/unsafe HTTP method/');

        $this->render("{% set f = csrf_action_form('/filter', 'filter', 'GET') %}{{ form_start(f) }}{{ form_end(f) }}");
    }

    public function testFieldsBuildFlatTypedForm(): void
    {
        $view = $this->extension()->csrfActionForm(
            '/locale/en',
            'account_locale',
            named: true,
            fields: ['redirect' => '/dashboard', 'empty' => null, 'note' => 'hi'],
            fieldTypes: ['note' => 'text'],
            fieldOptions: ['note' => ['attr' => ['data-x' => '1']]],
        );

        self::assertSame('', $view->vars['full_name'], 'fields force an empty-prefix form even when named');
        self::assertSame('redirect', $view->children['redirect']->vars['full_name']);
        self::assertSame('/dashboard', $view->children['redirect']->vars['value']);
        self::assertSame('', $view->children['empty']->vars['value']);
        self::assertContains('text', $view->children['note']->vars['block_prefixes']);
        self::assertSame('1', $view->children['note']->vars['attr']['data-x']);
        self::assertSame('_token', $view->children['_token']->vars['full_name']);
        self::assertSame('token:account_locale', $view->children['_token']->vars['value']);
    }

    public function testRendersNamedFormInTwig(): void
    {
        $html = $this->render("{% set form = csrf_action_form('/retry/5', 'retry') %}{{ form_start(form) }}<button>Retry</button>{{ form_end(form) }}");

        self::assertStringContainsString('action="/retry/5"', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('name="csrf_only[_token]"', $html);
        self::assertStringContainsString('value="token:retry"', $html);
    }

    public function testRendersKitForkCallWithNamedArgumentsInTwig(): void
    {
        $html = $this->render(
            "{% set form = csrf_action_form('/kit', 'kit_action', named: false, csrfFieldName: '_csrf_token') %}{{ form_start(form) }}{{ form_end(form) }}"
            . "{% set f2 = csrf_action_form('/locale', 'guest_locale', fields: {redirect: '/back'}) %}{{ form_start(f2) }}{{ form_end(f2) }}"
            . "{% set f3 = csrf_action_form('/item/9', 'item_delete', 'DELETE') %}{{ form_start(f3) }}{{ form_end(f3) }}",
        );

        self::assertStringContainsString('name="_csrf_token"', $html);
        self::assertStringContainsString('value="token:kit_action"', $html);
        self::assertStringContainsString('name="redirect"', $html);
        self::assertStringContainsString('value="/back"', $html);
        self::assertStringContainsString('value="token:guest_locale"', $html);
        self::assertStringContainsString('action="/item/9"', $html);
        self::assertStringContainsString('name="_method" value="DELETE"', $html);
    }

    private function extension(): CsrfActionFormExtension
    {
        $tokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $tokenManager->method('getToken')->willReturnCallback(static fn (string $id): CsrfToken => new CsrfToken($id, 'token:' . $id));

        $typeMap = new FormTypeMap();
        $merger  = new FormOptionsMerger(
            ['default' => ['translation_domain' => 'messages', 'auto_placeholder' => false, 'auto_help' => false, 'defaults' => ['attr' => [], 'row_attr' => [], 'help_attr' => []], 'field_types' => []]],
            'default',
            new ConstraintDefinitionFactory(),
            null,
            $typeMap,
        );

        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($tokenManager))
            ->addTypes([new CsrfOnlyType($merger, $typeMap), new HiddenFieldsCsrfType($merger, $typeMap)])
            ->getFormFactory();

        return new CsrfActionFormExtension(new CsrfOnlyFormFactory($formFactory));
    }

    private function render(string $template): string
    {
        $root = dirname(__DIR__, 3);
        $fs   = new FilesystemLoader([$root . '/vendor/symfony/twig-bridge/Resources/views/Form']);

        $twig = new Environment(new ChainLoader([new ArrayLoader(['t.twig' => $template]), $fs]), ['strict_variables' => true, 'cache' => false]);
        $twig->addExtension(new TranslationExtension());
        $twig->addExtension(new FormExtension());
        $twig->addExtension($this->extension());

        $engine = new TwigRendererEngine(['form_div_layout.html.twig'], $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer($engine),
        ]));

        return $twig->render('t.twig');
    }
}
