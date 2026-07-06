<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class NotificationTemplatePolicy
{
    public function viewAny(User $user, int $eventId): bool
    {
        return $this->canManageEventTemplates($user, $eventId);
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        return $this->canManageEventTemplates($user, $template->event_id);
    }

    public function create(User $user, int $eventId): bool
    {
        return $this->canManageEventTemplates($user, $eventId);
    }

    public function update(User $user, NotificationTemplate $template): bool
    {
        return $this->canManageEventTemplates($user, $template->event_id);
    }

    public function delete(User $user, NotificationTemplate $template): bool
    {
        return $this->canManageEventTemplates($user, $template->event_id);
    }

    private function canManageEventTemplates(User $user, int $eventId): bool
    {
        $event = Event::query()->find($eventId);

        if ($event === null) {
            return false;
        }

        return Gate::forUser($user)->allows('manageSettings', $event);
    }
}
