<?php

declare(strict_types=1);

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use Illuminate\Contracts\Auth\StatefulGuard;
use Tests\TestCase;

uses(TestCase::class);

it('type-hints the stateful guard contract so stateful auth methods are statically safe', function (): void {
    $actions = [
        LoginUserAction::class,
        RegisterUserAction::class,
        LogoutUserAction::class,
    ];

    foreach ($actions as $action) {
        $type = (new ReflectionClass($action))
            ->getConstructor()
            ->getParameters()[0]
            ->getType();

        expect($type)->not->toBeNull()
            ->and($type->getName())->toBe(StatefulGuard::class, "expected {$action} to depend on StatefulGuard");
    }
});
