<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormOptionsMerger;

/** Test double with wrong parameter count (must be skipped by the compiler pass). */
final class DummyFormTypeWithTwoParams
{
    public function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger, bool $enabled): static
    {
        return $this;
    }
}
