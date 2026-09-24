<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\AbstractGetFilterType;
use Symfony\Component\Form\FormBuilderInterface;

/** Test double exposing AbstractGetFilterType protected helpers. */
final class DemoGetFilterType extends AbstractGetFilterType
{
    public function getBlockPrefix(): string
    {
        return 'demo_filter';
    }

    /** @param FormBuilderInterface<mixed> $builder */
    public function exposeWithBuilder(FormBuilderInterface $builder, callable $callback): void
    {
        $this->withBuilder($builder, $callback);
    }

    /** @param array<string, mixed> $options */
    public function exposeAddHiddenFilterField(string $name, array $options = []): void
    {
        $this->addHiddenFilterField($name, $options);
    }

    /** @param array<string, mixed> $options */
    public function exposeAddFilterSelect(string $name, array $options): void
    {
        $this->addFilterSelect($name, $options);
    }
}
