<?php

declare(strict_types=1);

namespace App\Model;

/**
 * DTO for nested form demo: contact fields + embedded address.
 */
class ContactWithAddress
{
    public ?string $fullName = null;
    public ?string $email = null;
    public ?Address $address = null;

    public function __construct()
    {
        $this->address = new Address();
    }
}
