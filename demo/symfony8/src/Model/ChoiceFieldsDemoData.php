<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Demo DTO for Form Kit choice helpers (select, multiselect, radios, checkboxes, select-all).
 */
final class ChoiceFieldsDemoData
{
    public ?string $country = null;

    /** @var list<string> */
    public array $hobbies = [];

    public ?string $priority = 'normal';

    /** @var list<string> */
    public array $tags = [];

    public bool $agree = false;

    /** @var list<string> */
    public array $permissions = [];
}
