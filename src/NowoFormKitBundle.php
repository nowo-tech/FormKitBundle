<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle;

use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle to reduce repetitive form field options.
 *
 * Provides convention-based translation keys (form_snake.field_snake.label,
 * .placeholder, .help), configurable default attr/row_attr and translation_domain
 * via YAML config, and cascading option merge: global → field type → form → field.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoFormKitBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new FormKitExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new FormOptionsMergerInjectorCompilerPass());
    }
}
