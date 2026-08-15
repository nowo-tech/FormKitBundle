<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Builds rootless GET forms so field names stay query-string friendly.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class GetFilterFormFactory
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return FormInterface<mixed>
     */
    public function create(string $type, array $data = [], array $options = []): FormInterface
    {
        return $this->formFactory->createNamed('', $type, $data, $options);
    }
}
