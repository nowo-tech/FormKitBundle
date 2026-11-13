<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Port: builds validator constraints from configuration definitions (short names, FQCNs, `{Name: options}` items).
 *
 * The default implementation is {@see ConstraintDefinitionFactory}; alias this interface to your own service to
 * replace it.
 */
interface ConstraintDefinitionFactoryInterface
{
    /**
     * @param list<mixed> $definitions
     *
     * @return list<Constraint>
     */
    public function create(array $definitions, ?string $messageKeyPrefix = null): array;
}
