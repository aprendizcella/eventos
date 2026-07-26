<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\SendBulkMessageAction;
use App\DataTransferObjects\Notifications\SendBulkMessageDto;
use App\Enums\NotificationLogStatus;
use App\Jobs\Notifications\SendBulkEmailJob;
use App\Mail\BulkEventMessageMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Organizer;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates pending log and dispatches send bulk message job with afterCommit', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $event = Event::factory()->create();

    // Crear asistentes segmentables
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'email' => 'attendee@example.com',
    ]);

    $dto = new SendBulkMessageDto(
        eventId: $event->event_id,
        subject: 'Campaña Importante',
        body: 'Hola {{first_name}} de {{event_title}}',
    );

    $action = resolve(SendBulkMessageAction::class);
    $log = $action($dto, $user->id);

    expect($log->subject)->toBe('Campaña Importante')
        ->and($log->recipient_count)->toBe(1)
        ->and($log->status)->toBe(NotificationLogStatus::Pending);

    Queue::assertPushed(SendBulkEmailJob::class, fn ($job) => $job->notificationLogId === $log->notification_log_id);
});

it('processes sending queue and parses placeholders correctly', function (): void {
    Mail::fake();

    $user = User::factory()->create();
    $event = Event::factory()->create(['title' => 'Concierto de Rock']);

    // Destinatario
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan@example.com',
        'unique_code' => 'TKT-12345',
    ]);

    // Crear log directamente
    $log = NotificationLog::query()->create([
        'event_id' => $event->event_id,
        'sent_by_user_id' => $user->id,
        'subject' => 'Campaña test',
        'body' => 'Hola {{first_name}} {{last_name}}, tu ticket es {{ticket_code}} para {{event_title}}',
        'recipient_count' => 1,
        'status' => NotificationLogStatus::Pending,
        'filter_criteria' => [],
    ]);

    $job = new SendBulkEmailJob($log->notification_log_id);
    $job->handle();

    $log->refresh();

    expect($log->status)->toBe(NotificationLogStatus::Completed)
        ->and($log->completed_at)->not->toBeNull();

    Mail::assertQueued(BulkEventMessageMail::class, fn ($mail) => $mail->hasTo($attendee->email)
        && $mail->mailSubject === 'Campaña test'
        && str_contains((string) $mail->mailBody, 'Hola Juan Pérez')
        && str_contains((string) $mail->mailBody, 'tu ticket es TKT-12345')
        && str_contains((string) $mail->mailBody, 'para Concierto de Rock'));
});

it('processes the queued mailable through the transport without leaking tenant context', function (): void {
    Config::set('queue.default', 'sync');
    Config::set('mail.default', 'array');

    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    $user = User::factory()->create();
    $event = Event::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'title' => 'Concierto de Rock',
    ]);
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'first_name' => 'Juan',
        'email' => 'juan@example.com',
    ]);
    $log = NotificationLog::query()->create([
        'event_id' => $event->event_id,
        'sent_by_user_id' => $user->id,
        'subject' => 'Campaña test',
        'body' => 'Hola {{first_name}}',
        'recipient_count' => 1,
        'status' => NotificationLogStatus::Pending,
        'filter_criteria' => [],
    ]);

    /** @var ArrayTransport $transport */
    $transport = Mail::mailer()->getSymfonyTransport();
    $transport->flush();
    $tenantDuringTransport = null;
    EventFacade::listen(MessageSending::class, function () use (&$tenantDuringTransport): void {
        $tenantDuringTransport = Organizer::checkCurrent();
    });

    expect(fn (): mixed => dispatch(new SendBulkEmailJob($log->notification_log_id)))
        ->not->toThrow(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

    $log->refresh();
    $messages = $transport->messages();
    $message = $messages->first()?->getOriginalMessage();

    expect($log->status)->toBe(NotificationLogStatus::Completed)
        ->and($messages)->toHaveCount(1)
        ->and($message)->not->toBeNull()
        ->and($message?->getTo()[0]->getAddress())->toBe('juan@example.com')
        ->and($message?->getSubject())->toBe('Campaña test')
        ->and($message?->getHtmlBody())->toContain('Hola Juan')
        ->and($tenantDuringTransport)->toBeFalse()
        ->and(Organizer::checkCurrent())->toBeFalse();
});

it('enforces strict idempotency and does not send duplicate emails on job retry', function (): void {
    Mail::fake();

    $user = User::factory()->create();
    $event = Event::factory()->create();
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'email' => 'juan@example.com',
    ]);

    // Simular que el job falló a medias y ya registró un destinatario como 'queued'
    $log = NotificationLog::query()->create([
        'event_id' => $event->event_id,
        'sent_by_user_id' => $user->id,
        'subject' => 'Campaña repetida',
        'body' => 'Contenido',
        'recipient_count' => 1,
        'status' => NotificationLogStatus::Pending,
        'filter_criteria' => [],
    ]);

    DB::table('notification_recipient_log')->insert([
        'notification_log_id' => $log->notification_log_id,
        'attendee_id' => $attendee->attendee_id,
        'status' => 'queued',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Ejecutar el job. Debería ignorar al destinatario y no encolar nada
    $job = new SendBulkEmailJob($log->notification_log_id);
    $job->handle();

    Mail::assertNotQueued(BulkEventMessageMail::class);
});

it('retries failed mail queues by reusing pending and failed outbox entries', function (): void {
    Mail::fake();

    $user = User::factory()->create();
    $event = Event::factory()->create();
    $order = TicketOrder::factory()->create(['event_id' => $event->event_id]);
    $attendee = Attendee::factory()->create([
        'ticket_order_id' => $order->ticket_order_id,
        'email' => 'juan@example.com',
    ]);

    $log = NotificationLog::query()->create([
        'event_id' => $event->event_id,
        'sent_by_user_id' => $user->id,
        'subject' => 'Campaña reintentada',
        'body' => 'Contenido',
        'recipient_count' => 1,
        'status' => NotificationLogStatus::Pending,
        'filter_criteria' => [],
    ]);

    // Simular que quedó en failed de un intento fallido previo
    DB::table('notification_recipient_log')->insert([
        'notification_log_id' => $log->notification_log_id,
        'attendee_id' => $attendee->attendee_id,
        'status' => 'failed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Ejecutar. El job debe "reclamar" el registro fallido y procesar el envío
    $job = new SendBulkEmailJob($log->notification_log_id);
    $job->handle();

    Mail::assertQueued(BulkEventMessageMail::class);

    $recipientLog = DB::table('notification_recipient_log')
        ->where('notification_log_id', $log->notification_log_id)
        ->where('attendee_id', $attendee->attendee_id)
        ->first();

    expect($recipientLog->status)->toBe('queued');
});
