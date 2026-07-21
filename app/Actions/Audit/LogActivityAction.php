<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Override;
use Spatie\Activitylog\Actions\LogActivityAction as SpatieLogActivityAction;

class LogActivityAction extends SpatieLogActivityAction
{
    #[Override]
    public function execute(Model $activity, string $description): Model
    {
        // 1. Check explicit properties in JSON payload
        $activityProperties = $activity->getAttribute('properties');
        $properties = ($activityProperties instanceof \Illuminate\Support\Collection)
            ? $activityProperties->toArray()
            : (is_array($activityProperties) ? $activityProperties : []);
        $explicitGlobal = null;
        $explicitOrganizerId = null;

        if (array_key_exists('is_global', $properties)) {
            $explicitGlobal = (bool) $properties['is_global'];
            unset($properties['is_global']);
        }

        if (array_key_exists('organizer_id', $properties)) {
            $explicitOrganizerId = $properties['organizer_id'] !== null ? (int) $properties['organizer_id'] : null;
            unset($properties['organizer_id']);
        }

        // Put cleaned properties back
        $activity->setAttribute('properties', collect($properties));

        // 2. Resolve Current Tenant
        $currentTenant = Organizer::current();
        $currentTenantId = $currentTenant?->id;

        // 3. Apply validation invariants
        $resolvedOrganizerId = null;
        $resolvedGlobal = false;

        if ($explicitGlobal === true) {
            if ($explicitOrganizerId !== null) {
                throw new InvalidArgumentException('is_global=true implies organizer_id IS NULL');
            }
            $resolvedGlobal = true;
        } elseif ($explicitOrganizerId !== null) {
            if ($currentTenantId !== null && $explicitOrganizerId !== $currentTenantId) {
                throw new InvalidArgumentException('Explicit organizer_id conflicts with current tenant context');
            }
            $resolvedOrganizerId = $explicitOrganizerId;
            $resolvedGlobal = false;
        } elseif ($currentTenantId !== null) {
            // No explicit markers
            $resolvedOrganizerId = $currentTenantId;
            $resolvedGlobal = false;
        } else {
            $resolvedOrganizerId = null;
            $resolvedGlobal = false; // No tenant context implies unclassified legacy event
        }

        // Assign columns to model
        $activity->setAttribute('organizer_id', $resolvedOrganizerId);
        $activity->setAttribute('is_global', $resolvedGlobal);

        // Ensure all possible database columns exist on the model attribute array
        // to prevent mismatch keys in bulk inserts when buffering is enabled.
        $defaultColumns = [
            'subject_type' => null,
            'subject_id' => null,
            'event' => null,
            'causer_type' => null,
            'causer_id' => null,
            'attribute_changes' => null,
            'properties' => null,
            'organizer_id' => null,
            'is_global' => false,
        ];

        foreach ($defaultColumns as $column => $default) {
            if (!$activity->hasAttribute($column) && $activity->getAttribute($column) === null) {
                $activity->setAttribute($column, $default);
            }
        }

        return parent::execute($activity, $description);
    }
}
