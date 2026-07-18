<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Form\ConditionalFieldsDemoType;
use App\Model\ConditionalFieldsDemoData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Live re-render of Form Kit conditional fields (PRE_SET_DATA / PRE_SUBMIT).
 *
 * Changing account_type updates formValues; Live auto-submits on re-render so
 * FormEvents adapt company vs individual fields without a full page submit.
 */
#[AsLiveComponent]
final class ConditionalFieldsLive extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?ConditionalFieldsDemoData $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            ConditionalFieldsDemoType::class,
            $this->initialFormData ?? new ConditionalFieldsDemoData(),
        );
    }
}
