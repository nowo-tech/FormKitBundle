<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormKitAbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/** Test double exposing FormKitAbstractType protected helpers. */
final class ExposedFormKitAbstractType extends FormKitAbstractType
{
    public function getBlockPrefix(): string
    {
        return 'contact_form';
    }

    /** @return array<string, mixed> */
    public function exposeTwigOwnedChromeOptions(): array
    {
        return $this->twigOwnedChromeOptions();
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string, mixed> $options
     */
    public function exposeAddChoice(FormBuilderInterface $builder, string $name, array $options): void
    {
        $this->withBuilder($builder, function () use ($name, $options): void {
            $this->addChoiceWithFormPlaceholder($name, $options);
        });
    }

    public function exposeAddChoiceUnbound(string $name): void
    {
        $this->addChoiceWithFormPlaceholder($name, []);
    }
}
