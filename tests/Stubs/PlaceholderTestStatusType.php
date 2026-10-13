<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Tests\Stubs;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ChoiceType subtype that does not extend ChoiceType (like EntityType / EnumType).
 *
 * @extends AbstractType<mixed>
 */
final class PlaceholderTestStatusType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['choices' => ['Open' => 'open', 'Closed' => 'closed']]);
    }
}
