<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\Twig;

use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use Nowo\FormKitBundle\Twig\HelpModalTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class HelpModalTwigExtensionTest extends TestCase
{
    public function testPrintsTheSameRequestNonceInMetaAndFunction(): void
    {
        $request = new Request();
        $request->headers->set(HelpModalNonce::HEADER, 'AbCdEfGhIjKlMnOpQrStUvWx');
        $stack = new RequestStack();
        $stack->push($request);

        $twig = new Environment(new ArrayLoader([
            'page' => '<head>{{ nowo_form_kit_help_modal_meta() }}</head>{{ nowo_form_kit_help_modal_nonce() }}',
        ]));
        $twig->addExtension(new HelpModalTwigExtension(new HelpModalNonce($stack)));

        self::assertSame(
            '<head><meta name="nowo-form-kit-help-modal-nonce" content="AbCdEfGhIjKlMnOpQrStUvWx"></head>AbCdEfGhIjKlMnOpQrStUvWx',
            $twig->render('page'),
        );
    }

    public function testRegistersBothFunctions(): void
    {
        $names = [];
        foreach ((new HelpModalTwigExtension(new HelpModalNonce()))->getFunctions() as $function) {
            $names[] = $function->getName();
        }

        self::assertSame(['nowo_form_kit_help_modal_nonce', 'nowo_form_kit_help_modal_meta'], $names);
    }
}
