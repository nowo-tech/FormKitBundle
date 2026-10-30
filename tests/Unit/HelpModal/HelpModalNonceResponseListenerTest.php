<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Unit\HelpModal;

use Nowo\FormKitBundle\HelpModal\HelpModalNonce;
use Nowo\FormKitBundle\HelpModal\HelpModalNonceResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class HelpModalNonceResponseListenerTest extends TestCase
{
    public function testSubscribesToKernelResponse(): void
    {
        self::assertSame(
            [KernelEvents::RESPONSE => ['onKernelResponse', -255]],
            HelpModalNonceResponseListener::getSubscribedEvents(),
        );
    }

    public function testMarksResponsesThatReusedTheHeaderPrivateAndVary(): void
    {
        $request = new Request();
        $request->attributes->set(HelpModalNonce::REUSED_ATTRIBUTE, true);
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->setVary('Accept-Language');

        (new HelpModalNonceResponseListener())->onKernelResponse($this->event($request, $response, HttpKernelInterface::MAIN_REQUEST));

        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertFalse($response->headers->hasCacheControlDirective('public'));
        self::assertSame(['Accept-Language', HelpModalNonce::HEADER], $response->getVary());
    }

    public function testLeavesOtherResponsesUntouched(): void
    {
        $listener = new HelpModalNonceResponseListener();

        $plain = new Response();
        $plain->setPublic();
        $listener->onKernelResponse($this->event(new Request(), $plain, HttpKernelInterface::MAIN_REQUEST));
        self::assertTrue($plain->headers->hasCacheControlDirective('public'));
        self::assertSame([], $plain->getVary());

        $subRequest = new Request();
        $subRequest->attributes->set(HelpModalNonce::REUSED_ATTRIBUTE, true);
        $sub = new Response();
        $sub->setPublic();
        $listener->onKernelResponse($this->event($subRequest, $sub, HttpKernelInterface::SUB_REQUEST));
        self::assertTrue($sub->headers->hasCacheControlDirective('public'));
    }

    private function event(Request $request, Response $response, int $type): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, $type, $response);
    }
}
