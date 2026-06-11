<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Demo DTO for Symfony UX Autocomplete (ChoiceType + autocomplete option) and Form Kit {@see FormOptionsTrait::addAutocompleteField()}.
 */
final class AutocompleteDemoData
{
    public ?string $topic = null;

    /** @var list<string>|null */
    public ?array $skills = null;
}
