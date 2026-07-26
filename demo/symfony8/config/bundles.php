<?php

declare(strict_types=1);
use A2lix\TranslationFormBundle\A2lixTranslationFormBundle;
use FOS\CKEditorBundle\FOSCKEditorBundle;
use Nowo\Ckeditor5EditorBundle\NowoCkeditor5EditorBundle;
use Nowo\FormKitBundle\NowoFormKitBundle;
use Nowo\IconSelectorBundle\NowoIconSelectorBundle;
use Nowo\OtpInputBundle\NowoOtpInputBundle;
use Nowo\PasswordStrengthBundle\PasswordStrengthBundle;
use Nowo\PasswordToggleBundle\NowoPasswordToggleBundle;
use Nowo\PhoneInputBundle\NowoPhoneInputBundle;
use Nowo\SelectAllChoiceBundle\NowoSelectAllChoiceBundle;
use Nowo\TiptapEditorBundle\NowoTiptapEditorBundle;
use Nowo\TwigInspectorBundle\NowoTwigInspectorBundle;
use Pentatrion\ViteBundle\PentatrionViteBundle;
use Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\Autocomplete\AutocompleteBundle;
use Symfony\UX\Cropperjs\CropperjsBundle;
use Symfony\UX\Dropzone\DropzoneBundle;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

return [
    FrameworkBundle::class            => ['all' => true],
    TwigBundle::class                 => ['all' => true],
    PentatrionViteBundle::class       => ['all' => true],
    SensiolabsTypeScriptBundle::class => ['dev' => true, 'test' => true],
    DebugBundle::class                => ['dev' => true, 'test' => true],
    WebProfilerBundle::class          => ['dev' => true, 'test' => true],
    NowoTwigInspectorBundle::class    => ['dev' => true, 'test' => true],
    StimulusBundle::class             => ['all' => true],
    AutocompleteBundle::class         => ['all' => true],
    DropzoneBundle::class             => ['all' => true],
    CropperjsBundle::class            => ['all' => true],
    A2lixTranslationFormBundle::class => ['all' => true],
    NowoFormKitBundle::class          => ['all' => true],
    NowoSelectAllChoiceBundle::class  => ['all' => true],
    FOSCKEditorBundle::class          => ['all' => true],
    NowoOtpInputBundle::class         => ['all' => true],
    NowoPhoneInputBundle::class       => ['all' => true],
    NowoPasswordToggleBundle::class   => ['all' => true],
    PasswordStrengthBundle::class     => ['all' => true],
    NowoIconSelectorBundle::class     => ['all' => true],
    UXIconsBundle::class              => ['all' => true],
    NowoTiptapEditorBundle::class     => ['all' => true],
    NowoCkeditor5EditorBundle::class  => ['all' => true],
    TwigComponentBundle::class        => ['all' => true],
    LiveComponentBundle::class        => ['all' => true],
];
