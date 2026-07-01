<?php

declare(strict_types=1);

use App\Actions\PromoCodes\CreatePromoCodeAction;
use App\DataTransferObjects\PromoCodes\CreatePromoCodeDto;
use App\Enums\PromoCodeType;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\PromoCodeValidator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a promo code via action', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $creator = User::factory()->create();

    $dto = new CreatePromoCodeDto(
        code: 'SAVE10',
        type: PromoCodeType::Percentage,
        value: 10.00,
        max_uses: 50,
        start_at: now()->subDay(),
        end_at: now()->addDay(),
        status: 'active',
    );

    $action = resolve(CreatePromoCodeAction::class);
    $promoCode = $action($event, $dto, $creator);

    expect($promoCode)->toBeInstanceOf(PromoCode::class)
        ->and($promoCode->code)->toBe('SAVE10')
        ->and($promoCode->value)->toBe(10.00)
        ->and($promoCode->event_id)->toBe($event->event_id);

    $this->assertDatabaseHas('promo_code', [
        'event_id' => $event->event_id,
        'code' => 'SAVE10',
        'status' => 'active',
    ]);
});

it('enforces composite uniqueness on event_id and code', function (): void {
    $organizer = Organizer::factory()->create();
    $event1 = Event::factory()->create(['organizer_id' => $organizer->id]);
    $event2 = Event::factory()->create(['organizer_id' => $organizer->id]);

    // Create promo code for event 1
    PromoCode::factory()->create([
        'event_id' => $event1->event_id,
        'code' => 'SUMMER50',
    ]);

    // Same code on different event should be allowed
    $promoCode2 = PromoCode::factory()->create([
        'event_id' => $event2->event_id,
        'code' => 'SUMMER50',
    ]);
    expect($promoCode2)->toBeInstanceOf(PromoCode::class);

    // Same code on same event should trigger a DatabaseException (integrity constraint violation)
    $this->expectException(Illuminate\Database\QueryException::class);
    PromoCode::factory()->create([
        'event_id' => $event1->event_id,
        'code' => 'SUMMER50',
    ]);
});

it('validates promo codes correctly using PromoCodeValidator', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $validator = resolve(PromoCodeValidator::class);

    // 1. Valid promo code
    $validCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'VALID',
        'status' => 'active',
        'start_at' => now()->subDay(),
        'end_at' => now()->addDay(),
        'max_uses' => 10,
        'uses_count' => 5,
    ]);
    expect($validator->isValid($validCode, $event->event_id))->toBeTrue();

    // 2. Invalid status
    $inactiveCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'INACTIVE',
        'status' => 'inactive',
    ]);
    expect($validator->isValid($inactiveCode, $event->event_id))->toBeFalse();

    // 3. Not started yet
    $futureCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'FUTURE',
        'status' => 'active',
        'start_at' => now()->addDay(),
    ]);
    expect($validator->isValid($futureCode, $event->event_id))->toBeFalse();

    // 4. Already expired
    $expiredCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'EXPIRED',
        'status' => 'active',
        'end_at' => now()->subDay(),
    ]);
    expect($validator->isValid($expiredCode, $event->event_id))->toBeFalse();

    // 5. Max uses reached
    $exhaustedCode = PromoCode::factory()->create([
        'event_id' => $event->event_id,
        'code' => 'EXHAUSTED',
        'status' => 'active',
        'max_uses' => 5,
        'uses_count' => 5,
    ]);
    expect($validator->isValid($exhaustedCode, $event->event_id))->toBeFalse();

    // 6. Wrong event
    $wrongEvent = Event::factory()->create(['organizer_id' => $organizer->id]);
    expect($validator->isValid($validCode, $wrongEvent->event_id))->toBeFalse();
});
