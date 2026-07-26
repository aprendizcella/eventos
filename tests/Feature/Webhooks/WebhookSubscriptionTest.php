<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Models\Webhook;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

function organizerAdministrator(Organizer $organizer): User
{
    $user = User::factory()->create();

    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    return $user;
}

function webhookEndpoint(Organizer $organizer): string
{
    return route('api.organizers.webhooks.index', $organizer);
}

it('allows an organizer administrator to create and list only their subscriptions', function (): void {
    $organizer = Organizer::factory()->create();
    $administrator = organizerAdministrator($organizer);

    Sanctum::actingAs($administrator);

    $created = $this->postJson(webhookEndpoint($organizer), [
        'endpoint' => 'https://hooks.example.com:443/payment-completed',
    ]);

    $created->assertCreated()
        ->assertJsonPath('data.endpoint', 'https://hooks.example.com:443/payment-completed')
        ->assertJsonPath('data.enabled', true)
        ->assertJsonStructure(['data' => ['id', 'endpoint', 'enabled', 'created_at', 'updated_at', 'secret']]);

    $secret = $created->json('data.secret');

    expect($secret)->toBeString()->not->toBeEmpty();

    $this->getJson(webhookEndpoint($organizer))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $created->json('data.id'))
        ->assertJsonMissingPath('data.0.secret');

    expect(Webhook::query()->firstOrFail()->secret)->toBe($secret)
        ->and(DB::table('webhook')->value('secret'))->not->toBe($secret);
});

it('rejects cross-organizer subscription reads and disables without disclosure', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();
    $administratorA = organizerAdministrator($organizerA);
    $webhookB = Webhook::factory()->for($organizerB)->create();

    Sanctum::actingAs($administratorA);

    $this->getJson(webhookEndpoint($organizerB))->assertForbidden();

    $this->deleteJson(route('api.organizers.webhooks.destroy', [$organizerA, $webhookB]))
        ->assertNotFound();

    expect($webhookB->fresh()->enabled)->toBeTrue();
});

it('disables a subscription idempotently for its organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $administrator = organizerAdministrator($organizer);
    $webhook = Webhook::factory()->for($organizer)->create();

    Sanctum::actingAs($administrator);

    $this->deleteJson(route('api.organizers.webhooks.destroy', [$organizer, $webhook]))->assertNoContent();
    $this->deleteJson(route('api.organizers.webhooks.destroy', [$organizer, $webhook]))->assertNoContent();

    expect($webhook->fresh()->enabled)->toBeFalse();
});

it('reveals a replacement secret once when rotating and redacts it from later resources', function (): void {
    $organizer = Organizer::factory()->create();
    $administrator = organizerAdministrator($organizer);
    $webhook = Webhook::factory()->for($organizer)->create(['secret' => 'previous-secret']);

    Sanctum::actingAs($administrator);

    $rotated = $this->postJson(route('api.organizers.webhooks.rotate', [$organizer, $webhook]));

    $rotated->assertOk()
        ->assertJsonPath('data.id', $webhook->webhook_id)
        ->assertJsonStructure(['data' => ['secret']]);

    $replacementSecret = $rotated->json('data.secret');

    expect($replacementSecret)->toBeString()
        ->not->toBeEmpty()
        ->not->toBe('previous-secret')
        ->and($webhook->fresh()->secret)->toBe($replacementSecret);

    $this->getJson(webhookEndpoint($organizer))
        ->assertOk()
        ->assertJsonMissingPath('data.0.secret');
});

it('rejects destinations that are not public https hostnames on port 443', function (string $endpoint): void {
    $organizer = Organizer::factory()->create();
    $administrator = organizerAdministrator($organizer);

    Sanctum::actingAs($administrator);

    $this->postJson(webhookEndpoint($organizer), ['endpoint' => $endpoint])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
})->with([
    'http scheme' => 'http://hooks.example.com:443/payment-completed',
    'non-standard port' => 'https://hooks.example.com:8443/payment-completed',
    'loopback hostname' => 'https://localhost:443/payment-completed',
    'ip literal' => 'https://127.0.0.1:443/payment-completed',
    'credentials' => 'https://user:password@hooks.example.com:443/payment-completed',
]);
