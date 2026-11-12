<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Kernel;

use Nowo\FormKitBundle\NowoFormKitBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function dirname;
use function getmypid;
use function sys_get_temp_dir;

/**
 * Host-like kernel for the kept-alive worker test: one probe route that builds and renders a Form Kit form.
 */
final class WorkerKeptAliveKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new NowoFormKitBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/nowo-form-kit-bundle-worker/' . getmypid() . '/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/nowo-form-kit-bundle-worker/' . getmypid() . '/log';
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret'                => 'test-secret',
            'test'                  => true,
            'http_method_override'  => false,
            'handle_all_throwables' => true,
            'php_errors'            => ['log' => true],
            'router'                => ['utf8' => true],
            'property_info'         => ['enabled' => false],
            'assets'                => ['enabled' => false],
            'csrf_protection'       => false,
        ]);
        $container->extension('twig', ['default_path' => __DIR__]);
        $container->services()
            ->set(WorkerProbeController::class)->autowire()->public()->tag('controller.service_arguments');
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('worker_probe', '/probe')->controller(WorkerProbeController::class);
    }
}
