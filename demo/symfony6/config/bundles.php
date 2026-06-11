<?php

declare(strict_types=1);

return [
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class          => ['all' => true],
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class         => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                   => ['all' => true],
    Pentatrion\ViteBundle\PentatrionViteBundle::class             => ['all' => true],
    Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle::class  => ['dev' => true, 'test' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class                 => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class     => ['dev' => true, 'test' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class       => ['dev' => true, 'test' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class               => ['all' => true],
    Symfony\UX\Autocomplete\AutocompleteBundle::class               => ['all' => true],
    Symfony\UX\Dropzone\DropzoneBundle::class                     => ['all' => true],
    Symfony\UX\Cropperjs\CropperjsBundle::class                   => ['all' => true],
    A2lix\AutoFormBundle\A2lixAutoFormBundle::class               => ['all' => true],
    A2lix\TranslationFormBundle\A2lixTranslationFormBundle::class => ['all' => true],
    Nowo\FormKitBundle\NowoFormKitBundle::class                   => ['all' => true],
    FOS\CKEditorBundle\FOSCKEditorBundle::class                   => ['all' => true],
];
