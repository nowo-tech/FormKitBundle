<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormOptionsMerger;

/** Test double for FormOptionsMergerInjectorCompilerPass reflection checks. */
final class DummyFormTypeWithSetter
{
    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): static
    {
        return $this;
    }
}
