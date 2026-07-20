<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

/** Test double with wrong parameter type (must be skipped by the compiler pass). */
final class DummyFormTypeWithWrongParamType
{
    public function setFormOptionsMerger(string $formOptionsMerger): static
    {
        return $this;
    }
}
