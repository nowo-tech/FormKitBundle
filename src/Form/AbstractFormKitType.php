<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\AbstractType;

/**
 * Optional base form type that uses FormOptionsMerger for convention-based field options.
 *
 * Extend this class and inject FormOptionsMerger (e.g. via service definition).
 * In buildForm() use withBuilder($builder, fn () => $this->addTextField('field_name')) or
 * $this->addWithDefaults($builder, 'field_name', TextType::class, []) to add fields with merged options.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
/**
 * @extends AbstractType<mixed>
 */
abstract class AbstractFormKitType extends AbstractType
{
    use FormOptionsTrait;
}
