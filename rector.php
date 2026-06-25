<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        LevelSetList::UP_TO_PHP_84,
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    ]);

    $rectorConfig->skip([
        // `Volt::route('/login', 'auth.login')` passes a Volt component alias,
        // not a class name. StringToClassConstantRector falsely rewrites it to
        // `\Illuminate\Auth\Events\Login::class`, which would break the route.
        // The same rule also targets the test AuthRouteRegistrar, whose string
        // controller FQCNs are documented as intentional (RED-phase loading).
        Rector\Transform\Rector\String_\StringToClassConstantRector::class => [
            __DIR__.'/routes/web.php',
            __DIR__.'/tests/Auth/AuthRouteRegistrar.php',
        ],
    ]);
};
