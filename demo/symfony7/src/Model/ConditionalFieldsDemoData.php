<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Demo model for conditional fields (account_type drives which name fields exist).
 */
final class ConditionalFieldsDemoData
{
    public ?string $account_type = 'individual';

    public ?string $company_name = null;

    public ?string $first_name = null;

    public ?string $last_name = null;
}
