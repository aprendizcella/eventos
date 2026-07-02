<?php

declare(strict_types=1);

use App\Mail\SecureAccessLinkMail;
use App\Models\TicketOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('requests and receives access link email, and blocks enumeration', function (): void {
    Mail::fake();

    // 1. Solicitar con correo inexistente: no envía correo pero devuelve éxito (sent = true)
    Livewire::test('public.orders.my-orders')
        ->set('email', 'ghost@example.com')
        ->call('requestLink')
        ->assertSet('sent', true);

    Mail::assertNotSent(SecureAccessLinkMail::class);

    // 2. Crear una orden y volver a solicitar
    $order = TicketOrder::factory()->create([
        'email' => 'buyer@example.com',
        'status' => 'completed',
    ]);

    Livewire::test('public.orders.my-orders')
        ->set('email', 'buyer@example.com')
        ->call('requestLink')
        ->assertSet('sent', true);

    Mail::assertSent(SecureAccessLinkMail::class, fn ($mail) => $mail->hasTo('buyer@example.com') && $mail->url !== '');
});

it('enforces atomic cache pull and invalidation on first magic link visit', function (): void {
    $email = 'user@example.com';
    $token = 'magic_token_123';

    // Guardar token en caché
    Cache::put('magic_access_token_'.$token, $email, now()->addMinutes(15));

    // Crear la URL firmada temporal
    $signedUrl = URL::temporarySignedRoute(
        'public.orders.view',
        now()->addMinutes(15),
        ['token' => $token],
    );

    // 1. Acceder por primera vez a través de HTTP GET
    // Esto simula la visita del usuario al hacer clic en el enlace de su correo
    $response = $this->get($signedUrl);
    $response->assertOk();

    // El token debe haberse consumido y eliminado de la caché atómicamente
    expect(Cache::has('magic_access_token_'.$token))->toBeFalse();

    // 2. Intentar acceder por segunda vez con el mismo token: debe fallar con 403
    // Limpiamos la sesión para simular que es otro intento o navegador
    session()->forget('verified_ticket_email');

    $invalidUrl = URL::temporarySignedRoute(
        'public.orders.view',
        now()->addMinutes(15),
        ['token' => $token],
    );

    $invalidResponse = $this->get($invalidUrl);
    $invalidResponse->assertForbidden();
});

it('blocks access if signature is invalid or session is missing', function (): void {
    $response = $this->get(route('public.orders.view'));
    $response->assertForbidden();
});
