<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormOptionsMerger;

/** Test double with a private setter (must be skipped by the compiler pass). */
final class DummyFormTypeWithPrivateSetter
{
    private function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        unset($formOptionsMerger);
    }
}
