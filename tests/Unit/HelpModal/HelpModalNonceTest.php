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

    private const SENT = 'AbCdEfGhIjKlMnOpQrStUvWx';

    /**
     * @param array<string, string> $headers
     *
     * @return array{string, Request}
     */
    private function nonceFor(array $headers): array
    {
        $request = Request::create('https://app.example/frame');
        $request->headers->set(HelpModalNonce::HEADER, self::SENT);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }
        $stack = new RequestStack();
        $stack->push($request);

        return [(new HelpModalNonce($stack))->get(), $request];
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function sameOriginFragments(): iterable
    {
        yield 'Turbo-Frame, no origin info' => [['Turbo-Frame' => 'f1']];
        yield 'Sec-Fetch-Site same-origin' => [['Turbo-Frame' => 'f1', 'Sec-Fetch-Site' => 'same-origin', 'Origin' => 'https://evil.example']];
        yield 'matching Origin' => [['Turbo-Frame' => 'f1', 'Origin' => 'https://app.example']];
        yield 'matching Referer' => [['Turbo-Frame' => 'f1', 'Referer' => 'https://app.example/page?x=1']];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('sameOriginFragments')]
    public function testReusesTheHeaderOnSameOriginTurboFrameRequests(array $headers): void
    {
        [$nonce, $request] = $this->nonceFor($headers);

        self::assertSame(self::SENT, $nonce);
        self::assertTrue($request->attributes->get(HelpModalNonce::REUSED_ATTRIBUTE));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function nonFragmentOrCrossOrigin(): iterable
    {
        yield 'top-level request (no Turbo-Frame)' => [[]];
        yield 'top-level with same-origin fetch' => [['Sec-Fetch-Site' => 'same-origin']];
        yield 'Sec-Fetch-Site cross-site' => [['Turbo-Frame' => 'f1', 'Sec-Fetch-Site' => 'cross-site']];
        yield 'Sec-Fetch-Site none' => [['Turbo-Frame' => 'f1', 'Sec-Fetch-Site' => 'none']];
        yield 'foreign Origin' => [['Turbo-Frame' => 'f1', 'Origin' => 'https://app.example.evil.test']];
        yield 'foreign Referer' => [['Turbo-Frame' => 'f1', 'Referer' => 'https://evil.test/https://app.example/']];
        yield 'scheme mismatch' => [['Turbo-Frame' => 'f1', 'Origin' => 'http://app.example']];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('nonFragmentOrCrossOrigin')]
    public function testNeverReusesTheHeaderOutsideSameOriginFragments(array $headers): void
    {
        [$nonce, $request] = $this->nonceFor($headers);

        self::assertNotSame(self::SENT, $nonce);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $nonce);
        self::assertNull($request->attributes->get(HelpModalNonce::REUSED_ATTRIBUTE));
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
        $request->headers->set('Turbo-Frame', 'f1');
        $stack = new RequestStack();
        $stack->push($request);

        $value = (new HelpModalNonce($stack))->get();

        self::assertNotSame($header, $value);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{24}$/', $value);
    }
}
