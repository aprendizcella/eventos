<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;
use App\Models\Webhook;
use App\Support\Organizers\OrganizerRoles;

final class WebhookPolicy
{
    public function viewAny(User $user, Organizer $organizer): bool
    {
        return $this->isOrganizerAdministrator($user, $organizer);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Webhook $webhook): bool
    {
        return $this->canManageWebhook($user, $webhook);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Organizer $organizer): bool
    {
        return $this->isOrganizerAdministrator($user, $organizer);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Webhook $webhook): bool
    {
        return $this->canManageWebhook($user, $webhook);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Webhook $webhook): bool
    {
        return $this->update($user, $webhook);
    }

    /**
     * Determine whether the user can restore the model.
     */
    private function isOrganizerAdministrator(User $user, Organizer $organizer): bool
    {
        $membership = $organizer->users()->whereKey($user)->first();

        return $membership?->pivot->getAttribute('role') === OrganizerRoles::Admin->value;
    }

    private function canManageWebhook(User $user, Webhook $webhook): bool
    {
        $organizer = $webhook->organizer;

        return $organizer instanceof Organizer && $this->isOrganizerAdministrator($user, $organizer);
    }
}
