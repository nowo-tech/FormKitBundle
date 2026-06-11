<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Demo DTO for Form Kit model transformers (money, JSON, CSV, bool, switch).
 */
final class DataTransformersDemoData
{
    public ?int $priceCents = 1999;

    /** @var array<string, mixed>|null */
    public ?array $metadata = ['locale' => 'en', 'version' => 1];

    /** @var list<string>|null */
    public ?array $tags = ['php', 'symfony', 'form'];

    public int $published = 1;

    public int $notifyOn = 1;
}
