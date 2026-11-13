<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection;

use Nowo\FormKitBundle\Form\FormOptionsMerger;
use Nowo\FormKitBundle\Form\FormOptionsMergerInterface;
use ReflectionClass;
use ReflectionNamedType;
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

        // The port alias lets a host replace the merger; the class id keeps working for setters typed with it.
        $mergerRef = new Reference(self::MERGER_SERVICE_ID);
        $portRef   = new Reference(FormOptionsMergerInterface::class);

        foreach (array_keys($container->findTaggedServiceIds('form.type')) as $id) {
            $def   = $container->getDefinition($id);
            $class = $def->getClass();
            if ($class === null) {
                continue;
            }
            if (!class_exists($class)) {
                continue;
            }
            $refl = new ReflectionClass($class);
            if (!$refl->hasMethod('setFormOptionsMerger')) {
                continue;
            }
            $method = $refl->getMethod('setFormOptionsMerger');
            if (!$method->isPublic() || $method->getNumberOfParameters() !== 1) {
                continue;
            }
            $params    = $method->getParameters();
            $paramType = $params[0]->getType();
            if (!$paramType instanceof ReflectionNamedType) {
                continue;
            }
            if ($paramType->getName() === FormOptionsMergerInterface::class) {
                $def->addMethodCall('setFormOptionsMerger', [$portRef]);
            } elseif ($paramType->getName() === FormOptionsMerger::class) {
                $def->addMethodCall('setFormOptionsMerger', [$mergerRef]);
            }
        }
    }
}
