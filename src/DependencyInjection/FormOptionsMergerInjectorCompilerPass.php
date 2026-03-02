<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects FormOptionsMerger into all form types that have setFormOptionsMerger().
 * Avoids having to configure each form type manually in services.yaml.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormOptionsMergerInjectorCompilerPass implements CompilerPassInterface
{
    private const MERGER_SERVICE_ID = FormOptionsMerger::class;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(self::MERGER_SERVICE_ID)) {
            return;
        }

        $mergerRef = new Reference(self::MERGER_SERVICE_ID);

        foreach ($container->findTaggedServiceIds('form.type') as $id => $tags) {
            $def = $container->getDefinition($id);
            $class = $def->getClass();
            if ($class === null) {
                continue;
            }
            if (!class_exists($class)) {
                continue;
            }
            try {
                $refl = new \ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }
            if (!$refl->hasMethod('setFormOptionsMerger')) {
                continue;
            }
            $method = $refl->getMethod('setFormOptionsMerger');
            if (!$method->isPublic() || $method->getNumberOfParameters() !== 1) {
                continue;
            }
            $params = $method->getParameters();
            $paramType = $params[0]->getType();
            if ($paramType instanceof \ReflectionNamedType && $paramType->getName() === FormOptionsMerger::class) {
                $def->addMethodCall('setFormOptionsMerger', [$mergerRef]);
            }
        }
    }
}
