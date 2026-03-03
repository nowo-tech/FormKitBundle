<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\AbstractType;

/**
 * Base form type that uses FormKitTrait with option merger and type map injected.
 *
 * Extend this type and use mergeFieldOptions() / addField() in buildForm()
 * to get cascading options and convention-based label/placeholder/help.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
abstract class FormKitAbstractType extends AbstractType
{
    use FormKitTrait;

    public function __construct(FormOptionsMerger $formOptionsMerger, FormTypeMap $formTypeMap)
    {
        $this->formOptionsMerger = $formOptionsMerger;
        $this->formTypeMap       = $formTypeMap;
    }
}
