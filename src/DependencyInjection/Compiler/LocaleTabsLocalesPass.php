<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_values;
use function is_array;
use function is_string;

/**
 * Fills the locale tabs defaults from the framework (2.13): `nowo_form_kit.locale_tabs.locales` = `kernel.enabled_locales`
 * when not configured, `nowo_form_kit.locale_tabs.default_locale` = `kernel.default_locale` when null. Runs at
 * compile time, when the FrameworkBundle parameters exist.
 */
final class LocaleTabsLocalesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('nowo_form_kit.locale_tabs.locales')) {
            return;
        }

        $locales = $container->getParameter('nowo_form_kit.locale_tabs.locales');
        if ((!is_array($locales) || $locales === []) && $container->hasParameter('kernel.enabled_locales')) {
            $enabled = $container->getParameter('kernel.enabled_locales');
            $container->setParameter('nowo_form_kit.locale_tabs.locales', is_array($enabled) ? array_values($enabled) : []);
        }

        $default = $container->hasParameter('nowo_form_kit.locale_tabs.default_locale') ? $container->getParameter('nowo_form_kit.locale_tabs.default_locale') : null;
        if (!is_string($default) && $container->hasParameter('kernel.default_locale')) {
            $kernelDefault = $container->getParameter('kernel.default_locale');
            $container->setParameter('nowo_form_kit.locale_tabs.default_locale', is_string($kernelDefault) ? $kernelDefault : null);
        }
    }
}
