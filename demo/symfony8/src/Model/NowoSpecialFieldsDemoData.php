<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Demo model for Nowo ecosystem special form fields.
 */
final class NowoSpecialFieldsDemoData
{
    public ?string $verificationCode = null;

    public ?string $mobilePhone = null;

    public ?string $secretPassword = null;

    public ?string $strengthOnlyPassword = null;

    public ?string $combinedPassword = null;

    public ?string $appIcon = null;

    /** @var list<string> */
    public array $keywords = [];

    public ?string $articleBody = null;

    public ?string $pageContent = null;

    public bool $confirmSlide = false;
}
