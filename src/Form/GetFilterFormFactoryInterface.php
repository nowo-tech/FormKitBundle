<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Port: builds rootless GET forms so field names stay query-string friendly.
 *
 * The default implementation is {@see GetFilterFormFactory}; alias this interface to your own service to replace it.
 */
interface GetFilterFormFactoryInterface
{
    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return FormInterface<mixed>
     */
    public function create(string $type, array $data = [], array $options = []): FormInterface;
}
