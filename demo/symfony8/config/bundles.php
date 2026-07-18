<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class         => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                   => ['all' => true],
    Pentatrion\ViteBundle\PentatrionViteBundle::class             => ['all' => true],
    Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class                 => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class     => ['dev' => true, 'test' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class       => ['dev' => true, 'test' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class               => ['all' => true],
    Symfony\UX\Autocomplete\AutocompleteBundle::class             => ['all' => true],
    Symfony\UX\Dropzone\DropzoneBundle::class                     => ['all' => true],
    Symfony\UX\Cropperjs\CropperjsBundle::class                   => ['all' => true],
    A2lix\TranslationFormBundle\A2lixTranslationFormBundle::class => ['all' => true],
    Nowo\FormKitBundle\NowoFormKitBundle::class                   => ['all' => true],
    Nowo\SelectAllChoiceBundle\NowoSelectAllChoiceBundle::class   => ['all' => true],
    FOS\CKEditorBundle\FOSCKEditorBundle::class                   => ['all' => true],
    Nowo\OtpInputBundle\NowoOtpInputBundle::class                 => ['all' => true],
    Nowo\PhoneInputBundle\NowoPhoneInputBundle::class             => ['all' => true],
    Nowo\PasswordToggleBundle\NowoPasswordToggleBundle::class     => ['all' => true],
    Nowo\PasswordStrengthBundle\PasswordStrengthBundle::class     => ['all' => true],
    Nowo\IconSelectorBundle\NowoIconSelectorBundle::class         => ['all' => true],
    Symfony\UX\Icons\UXIconsBundle::class                         => ['all' => true],
    Nowo\TiptapEditorBundle\NowoTiptapEditorBundle::class         => ['all' => true],
    Nowo\Ckeditor5EditorBundle\NowoCkeditor5EditorBundle::class   => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class           => ['all' => true],
    Symfony\UX\LiveComponent\LiveComponentBundle::class           => ['all' => true],
];
