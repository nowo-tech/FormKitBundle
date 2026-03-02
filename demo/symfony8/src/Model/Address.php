<?php

declare(strict_types=1);

namespace App\Model;

class Address
{
    public ?string $street = null;
    public ?string $number = null;
    public ?string $floor = null;
    public ?string $postalCode = null;
    public ?string $city = null;
    public ?string $province = null;
}
