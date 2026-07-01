<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

it('converts float decimal amounts to integer cents accurately with rounding formula', function (): void {
    // Definimos montos que suelen ser problemáticos en la representación de punto flotante de PHP
    $amounts = [
        '19.99' => 1999,
        '10.29' => 1029,
        '20.15' => 2015,
        '0.01' => 1,
        '0.00' => 0,
        '100.00' => 10000,
        '99.99' => 9999,
    ];

    foreach ($amounts as $decimal => $expectedCents) {
        $floatAmount = (float) $decimal;
        $cents = intval(round($floatAmount * 100));

        expect($cents)->toBe($expectedCents);
    }
});
