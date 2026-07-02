<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Mail\SecureAccessLinkMail;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final readonly class SendAccessLinkAction
{
    /**
     * Envía un Magic Link firmado al correo del usuario si tiene pedidos.
     */
    public function __invoke(string $email): void
    {
        $hasOrders = TicketOrder::query()->where('email', $email)->exists();

        if (!$hasOrders) {
            // Retorno temprano silencioso para evitar la enumeración de emails
            return;
        }

        // Generar un token aleatorio seguro
        $token = Str::random(40);

        // Guardar token en caché con TTL de 15 minutos
        Cache::put('magic_access_token_'.$token, $email, now()->addMinutes(15));

        // Crear la URL firmada temporal
        $url = URL::temporarySignedRoute(
            'public.orders.view',
            now()->addMinutes(15),
            ['token' => $token],
        );

        // Enviar email
        Mail::to($email)->send(new SecureAccessLinkMail($url, $email));
    }
}
