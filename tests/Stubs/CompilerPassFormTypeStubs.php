<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormOptionsMerger;

/**
 * Test doubles for FormOptionsMergerInjectorCompilerPass reflection checks.
 */
final class DummyFormTypeWithSetter
{
    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): static
    {
        return $this;
    }
}

final class DummyFormTypeWithoutSetter
{
}

final class DummyFormTypeWithPrivateSetter
{
    private function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        unset($formOptionsMerger);
    }
}
