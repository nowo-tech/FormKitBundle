<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Nowo\FormKitBundle\Form\FormOptionsMergerInterface;

/** Form type whose setter takes the merger port (like the bundle traits do). */
final class DummyFormTypeWithPortSetter
{
    public function setFormOptionsMerger(FormOptionsMergerInterface $formOptionsMerger): static
    {
        return $this;
    }
}
