<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\HelpModal;

use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function str_repeat;

final class HelpModalNonceTest extends TestCase
{
    public function testWithoutRequestReturnsFreshRandomBase64UrlValues(): void
    {
        $nonce = new HelpModalNonce();
        $a     = $nonce->get();
        $b     = (new HelpModalNonce(new RequestStack()))->get();

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $a);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $b);
        self::assertNotSame($a, $nonce->get());
    }

    public function testIsStableForTheMainRequestAndStoredInItsAttributes(): void
    {
        $main  = new Request();
        $stack = new RequestStack();
        $stack->push($main);
        $stack->push(new Request()); // sub-request shares the main request nonce

        $nonce = new HelpModalNonce($stack);
        $value = $nonce->get();

        self::assertSame($value, $nonce->get());
        self::assertSame($value, $main->attributes->get(HelpModalNonce::REQUEST_ATTRIBUTE));
    }

    public function testReusesAWellFormedNonceSentByAFragmentRequest(): void
    {
        $sent    = 'AbCdEfGhIjKlMnOpQrStUvWx';
        $request = new Request();
        $request->headers->set(HelpModalNonce::HEADER, $sent);
        $stack = new RequestStack();
        $stack->push($request);

        self::assertSame($sent, (new HelpModalNonce($stack))->get());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedHeaders(): iterable
    {
        yield 'too short' => ['abc'];
        yield 'markup' => ['"><img src=x onerror=alert(1)>aaaaaaaaaaaa'];
        yield 'too long' => [str_repeat('a', 129)];
        yield 'empty' => [''];
    }

    #[DataProvider('malformedHeaders')]
    public function testIgnoresMalformedHeaderValues(string $header): void
    {
        $request = new Request();
        $request->headers->set(HelpModalNonce::HEADER, $header);
        $stack = new RequestStack();
        $stack->push($request);

        $value = (new HelpModalNonce($stack))->get();

        self::assertNotSame($header, $value);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $value);
    }
}
