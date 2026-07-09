<?php

declare(strict_types=1);

use App\Actions\Payments\GenerateInvoiceNumberAction;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('generates number 1 for first invoice of an organizer in a year', function (): void {
    $organizer = Organizer::factory()->create();
    $action = resolve(GenerateInvoiceNumberAction::class);

    $result = $action($organizer);

    expect($result['year'])->toBe(now()->year)
        ->and($result['number'])->toBe(1)
        ->and($result['invoice_number'])->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('increments the number sequentially for the same organizer in the same year', function (): void {
    $organizer = Organizer::factory()->create();
    $action = resolve(GenerateInvoiceNumberAction::class);

    // Create first invoice and generate next number
    $first = $action($organizer);
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => $first['year'],
        'number' => $first['number'],
        'invoice_number' => $first['invoice_number'],
    ]);

    $second = $action($organizer);

    expect($second['year'])->toBe($first['year'])
        ->and($second['number'])->toBe($first['number'] + 1);
});

it('maintains separate sequences for different organizers', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();
    $action = resolve(GenerateInvoiceNumberAction::class);

    // Create invoice for organizer A
    $firstA = $action($organizerA);
    Invoice::factory()->create([
        'organizer_id' => $organizerA->id,
        'year' => $firstA['year'],
        'number' => $firstA['number'],
        'invoice_number' => $firstA['invoice_number'],
    ]);

    // Second invoice for organizer A
    $secondA = $action($organizerA);
    expect($secondA['number'])->toBe($firstA['number'] + 1);

    // First invoice for organizer B should still be 1
    $firstB = $action($organizerB);
    expect($firstB['number'])->toBe(1);
});

it('resets numbering each year for the same organizer', function (): void {
    $organizer = Organizer::factory()->create();

    // Seed invoices for previous year
    Invoice::factory()->create([
        'organizer_id' => $organizer->id,
        'year' => now()->year - 1,
        'number' => 5,
        'invoice_number' => 'INV-'.(now()->year - 1).'-0005',
    ]);

    $action = resolve(GenerateInvoiceNumberAction::class);
    $result = $action($organizer);

    expect($result['year'])->toBe(now()->year)
        ->and($result['number'])->toBe(1);
});

it('generates credit note prefix correctly', function (): void {
    $organizer = Organizer::factory()->create();
    $action = resolve(GenerateInvoiceNumberAction::class);

    $result = $action($organizer, InvoiceType::CreditNote);

    expect($result['invoice_number'])->toMatch('/^CN-\d{4}-\d{4}$/');
});

it('generates sequential numbers within a transaction', function (): void {
    $organizer = Organizer::factory()->create();
    $action = resolve(GenerateInvoiceNumberAction::class);

    $numbers = Illuminate\Support\Facades\DB::transaction(function () use ($organizer, $action): array {
        $first = $action($organizer);
        Invoice::factory()->create([
            'organizer_id' => $organizer->id,
            'year' => $first['year'],
            'number' => $first['number'],
            'invoice_number' => $first['invoice_number'],
        ]);

        $second = $action($organizer);

        return [$first['number'], $second['number']];
    });

    expect($numbers[1])->toBe($numbers[0] + 1);
});
