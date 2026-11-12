<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Integration\Worker;

use Nowo\FormKitBundle\Tests\Kernel\WorkerKeptAliveKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

use function gc_collect_cycles;
use function memory_get_usage;
use function sprintf;
use function str_contains;

/**
 * REQ-RUNTIME-002: ONE kernel, `services_resetter` / `kernel.reset` never called (FrankenPHP worker, kernel reset
 * false). N iterations alternate two users through `http_kernel` handle() + terminate(): each request builds a Form Kit
 * search form from its own query and renders it with Twig. Every response must contain only its own data and memory
 * must stay flat.
 */
final class WorkerKeptAliveKernelTest extends TestCase
{
    private const ITERATIONS = 1200;
    private const WARM_UP    = 100;
    private const MARGIN     = 4 * 1024 * 1024;

    public function testAlternatingUsersInOneKernelLeakNothingAndKeepMemoryFlat(): void
    {
        $kernel = new WorkerKeptAliveKernel('test', false);
        $kernel->boot();

        try {
            $httpKernel = $kernel->getContainer()->get('http_kernel');
            self::assertInstanceOf(HttpKernelInterface::class, $httpKernel);
            self::assertInstanceOf(TerminableInterface::class, $httpKernel);

            $baseline = 0;
            for ($i = 0; $i < self::ITERATIONS; ++$i) {
                if ($i === self::WARM_UP) {
                    gc_collect_cycles();
                    $baseline = memory_get_usage();
                }

                $who   = $i % 2 === 0 ? 'alpha' : 'beta';
                $other = $i % 2 === 0 ? 'beta' : 'alpha';

                $request  = Request::create('/probe', 'GET', ['q' => $who . '-secret-' . $i, 'who' => $who]);
                $response = $httpKernel->handle($request);
                $content  = (string) $response->getContent();

                self::assertSame(200, $response->getStatusCode(), sprintf('iteration %d', $i));
                self::assertTrue(str_contains($content, 'data-user="' . $who . '"'), sprintf('iteration %d', $i));
                self::assertTrue(str_contains($content, $who . '-secret-' . $i), sprintf('iteration %d', $i));
                self::assertFalse(str_contains($content, $other), sprintf('iteration %d: other user data present', $i));
                self::assertFalse(str_contains($content, '-secret-' . ($i - 1)), sprintf('iteration %d: previous request data present', $i));
                $httpKernel->terminate($request, $response);
            }

            gc_collect_cycles();
            $growth = memory_get_usage() - $baseline;
            self::assertLessThan(self::MARGIN, $growth, sprintf('memory grew by %d bytes over %d iterations', $growth, self::ITERATIONS - self::WARM_UP));
        } finally {
            $kernel->shutdown();
        }
    }
}
