<?php

declare(strict_types=1);

namespace Nowo\FormKitBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

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
        'checkbox' => CheckboxType::class,
        'choice'   => ChoiceType::class,
        'email'    => EmailType::class,
        'integer'  => IntegerType::class,
        'number'   => NumberType::class,
        'password' => PasswordType::class,
        'textarea' => TextareaType::class,
        'text'     => TextType::class,
        'url'      => UrlType::class,
    ];

    /**
     * Optional types (Symfony UX, A2lix, etc.). Only added when the class exists.
     * Autocomplete is not a standalone FormType; use entity attributes instead.
     *
     * @var array<string, string>
     */
    private const OPTIONAL = [
        'dropzone'     => 'Symfony\UX\Dropzone\Form\DropzoneType',
        'cropper'      => 'Symfony\UX\Cropperjs\Form\CropperType',
        'translations' => 'A2lix\TranslationFormBundle\Form\Type\TranslationsType',
    ];

    /** @var array<string, string> */
    private array $map;

    /** @param array<string, string> $typeMap From config (nowo_form_kit.type_map) */
    public function __construct(array $typeMap = [])
    {
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
        return $this->map[$snakeCaseType] ?? null;
    }

    /** @return list<string> */
    public function typeNames(): array
    {
        return array_keys($this->map);
    }
}
