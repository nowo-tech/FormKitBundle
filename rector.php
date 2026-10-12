<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/demo',
        __DIR__ . '/tests/Stubs',
        // Keeps the generic @return (FormBuilderInterface<mixed>|FormInterface<mixed>) PHPStan needs.
        RemoveUselessUnionReturnDocblockRector::class => [
            __DIR__ . '/src/Controller/FormKitControllerTrait.php',
        ],
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    );
