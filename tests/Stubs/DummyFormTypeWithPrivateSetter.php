<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use LogicException;
use Nowo\FormKitBundle\Form\Constraint\ConstraintDefinitionFactory;
use Nowo\FormKitBundle\Form\FormOptionsMerger;

/** Test double with a private setter (must be skipped by the compiler pass). */
final class DummyFormTypeWithPrivateSetter
{
    private ?FormOptionsMerger $storedMerger = null;

    private function setFormOptionsMerger(FormOptionsMerger $formOptionsMerger): void
    {
        $this->storedMerger = $formOptionsMerger;
    }

    public function touchPrivateSetter(): void
    {
        $this->setFormOptionsMerger(new FormOptionsMerger([], 'default', new ConstraintDefinitionFactory()));

        if (!$this->storedMerger instanceof FormOptionsMerger) {
            throw new LogicException('Private setter was not exercised.');
        }
    }
}
