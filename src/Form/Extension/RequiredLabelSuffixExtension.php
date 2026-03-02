<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Injects required_label_suffix into the view from the bundle config (default config).
 * The suffix is appended to the label in the form theme when the field is required.
 * The option is never passed to the form type; it is only read from config and used when building the label.
 */
final class RequiredLabelSuffixExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly array $configs,
        private readonly string $defaultConfigName,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $config = $this->configs[$this->defaultConfigName] ?? null;
        $view->vars['required_label_suffix'] = $config['required_label_suffix'] ?? null;
    }
}
