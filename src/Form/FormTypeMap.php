<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Dropzone\Form\DropzoneType;

/**
 * Maps snake_case type names to Symfony FormType FQCNs.
 *
 * Includes built-in Symfony types and optional UX/special types (e.g. Dropzone)
 * when the corresponding package is installed. Config type_map can add more.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FormTypeMap
{
    /** @var array<string, string> */
    private const BUILTIN = [
        'birthday'      => BirthdayType::class,
        'button'        => ButtonType::class,
        'checkbox'      => CheckboxType::class,
        'choice'        => ChoiceType::class,
        'collection'    => CollectionType::class,
        'color'         => ColorType::class,
        'country'       => CountryType::class,
        'currency'      => CurrencyType::class,
        'date'          => DateType::class,
        'date_interval' => DateIntervalType::class,
        'datetime'      => DateTimeType::class,
        'email'         => EmailType::class,
        'file'          => FileType::class,
        'hidden'        => HiddenType::class,
        'integer'       => IntegerType::class,
        'language'      => LanguageType::class,
        'locale'        => LocaleType::class,
        'money'         => MoneyType::class,
        'number'        => NumberType::class,
        'password'      => PasswordType::class,
        'percent'       => PercentType::class,
        'range'         => RangeType::class,
        'repeated'      => RepeatedType::class,
        'reset'         => ResetType::class,
        'search'        => SearchType::class,
        'submit'        => SubmitType::class,
        'tel'           => TelType::class,
        'text'          => TextType::class,
        'textarea'      => TextareaType::class,
        'time'          => TimeType::class,
        'timezone'      => TimezoneType::class,
        'url'           => UrlType::class,
        'week'          => WeekType::class,
    ];

    /**
     * Optional types (Symfony UX, A2lix, etc.). Only added when the class exists.
     * Autocomplete is not a standalone FormType; use entity attributes instead.
     *
     * @var array{dropzone: string, cropper: string, translations: string}
     */
    private const OPTIONAL = [
        'dropzone'     => DropzoneType::class,
        'cropper'      => CropperType::class,
        'translations' => 'A2lix\TranslationFormBundle\Form\Type\TranslationsType',
    ];

    /** @var array<string, string> */
    private array $map;

    /** @param array<string, string> $typeMap From config (nowo_form_kit.type_map) */
    public function __construct(array $typeMap = [])
    {
        /** @var array<string, string> $optionalResolved */
        $optionalResolved = [];
        foreach (self::OPTIONAL as $name => $fqcn) {
            if (class_exists($fqcn)) {
                $optionalResolved[$name] = $fqcn;
            }
        }
        $this->map = array_merge(self::BUILTIN, $optionalResolved, $typeMap);
    }

    /** @return class-string|null */
    public function resolve(string $snakeCaseType): ?string
    {
        /** @var class-string|null $resolved */
        $resolved = $this->map[$snakeCaseType] ?? null;

        return $resolved;
    }

    /** @return list<string> */
    public function typeNames(): array
    {
        return array_keys($this->map);
    }
}
