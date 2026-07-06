<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\NotificationTemplate;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('allows organizer admin to view settings and manage notification templates', function (): void {
    $admin = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->actingAs($admin);

    // 1. Probar el componente Volt de settings
    $component = Volt::test('organizers.events.event-settings', ['event' => $event])
        ->assertSet('auto_notify_waitlist', false)
        ->set('auto_notify_waitlist', true)
        ->set('sender_email', 'remitente@example.com')
        ->set('sender_name', 'Nombre Remitente')
        ->call('saveSettings')
        ->assertHasNoErrors();

    $event->refresh();
    expect($event->settings['auto_notify_waitlist'])->toBe(true)
        ->and($event->settings['sender_email'])->toBe('remitente@example.com');

    // 2. Probar creación de plantilla
    $component->set('template_name', 'Plantilla Test')
        ->set('template_subject', 'Asunto Especial')
        ->set('template_body', 'Cuerpo del mensaje de prueba')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    expect(NotificationTemplate::query()->where('event_id', $event->event_id)->count())->toBe(1);

    // 3. Probar edición de plantilla
    /** @var NotificationTemplate $template */
    $template = NotificationTemplate::query()->where('event_id', $event->event_id)->first();
    $component->call('editTemplate', $template->notification_template_id)
        ->assertSet('template_name', 'Plantilla Test')
        ->set('template_name', 'Plantilla Modificada')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    $template->refresh();
    expect($template->name)->toBe('Plantilla Modificada');

    // 4. Probar borrado de plantilla
    $component->call('deleteTemplate', $template->notification_template_id);

    expect(NotificationTemplate::query()->where('event_id', $event->event_id)->count())->toBe(0);
});

it('denies settings access to non-organizer members', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($user);

    Volt::test('organizers.events.event-settings', ['event' => $event])
        ->assertForbidden();
});
