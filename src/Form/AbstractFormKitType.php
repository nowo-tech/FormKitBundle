<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\AbstractType;

/**
 * Optional base form type that uses FormOptionsMerger for convention-based field options.
 *
 * Extend this class and inject FormOptionsMerger (e.g. via service definition).
 * In buildForm() use $this->addWithDefaults($builder, 'field_name', TextType::class, []) to add
 * fields with merged options (label, placeholder, help from form_snake.field_snake.*).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
abstract class AbstractFormKitType extends AbstractType
{
    use FormOptionsTrait;
}
