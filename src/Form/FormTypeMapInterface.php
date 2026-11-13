<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

/**
 * Port: maps snake_case field type names (`text`, `choice`, `phone`, ...) to form type classes.
 *
 * The default implementation is {@see FormTypeMap} (`FormTypeMap::NOWO_WIDGETS` stays on the class); alias this
 * interface to your own service to replace it.
 */
interface FormTypeMapInterface
{
    /**
     * @return class-string|null
     */
    public function resolve(string $snakeCaseType): ?string;

    /**
     * @return list<string>
     */
    public function typeNames(): array;
}
