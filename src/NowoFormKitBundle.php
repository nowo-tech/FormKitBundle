<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle;

use Nowo\FormKitBundle\DependencyInjection\Compiler\TwigPathsPass;
use Nowo\FormKitBundle\DependencyInjection\FormKitExtension;
use Nowo\FormKitBundle\DependencyInjection\FormOptionsMergerInjectorCompilerPass;
use Nowo\FormKitBundle\Form\Extension\StatelessCsrfTokenIdExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        if ($this->extension === null || $this->extension === false) {
            // @igor-ignore - Symfony bundle extension lazy-init at boot; not request state
            $this->extension = new FormKitExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigPathsPass());
        $container->addCompilerPass(new FormOptionsMergerInjectorCompilerPass());
    }

    /**
     * Publishes the `stateless_csrf.form_types` list to {@see StatelessCsrfTokenIdExtension}:
     * Symfony validates each type extension against its static getExtendedTypes().
     */
    public function boot(): void
    {
        parent::boot();

        if ($this->container instanceof ContainerInterface && $this->container->hasParameter('nowo_form_kit.stateless_csrf.form_types')) {
            /** @var list<class-string> $formTypes */
            $formTypes = $this->container->getParameter('nowo_form_kit.stateless_csrf.form_types');
            StatelessCsrfTokenIdExtension::configureExtendedTypes($formTypes);
        }
    }
}
