<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;
use Spatie\Activitylog\Actions\LogActivityAction as SpatieLogActivityAction;

class LogActivityAction extends SpatieLogActivityAction
{
    #[Override]
    public function execute(Model $activity, string $description): Model
    {
        [$explicitGlobal, $explicitOrganizerId] = $this->extractScopeProperties($activity);
        $this->applyScope($activity, $explicitGlobal, $explicitOrganizerId);
        $this->ensureDefaultColumns($activity);

        return parent::execute($activity, $description);
    }

    /**
     * @return array{?bool, ?int}
     */
    private function extractScopeProperties(Model $activity): array
    {
        $activityProperties = $activity->getAttribute('properties');
        $properties = [];

        if ($activityProperties instanceof Collection) {
            $properties = $activityProperties->toArray();
        }

        if (is_array($activityProperties)) {
            $properties = $activityProperties;
        }

        $explicitGlobal = null;
        $explicitOrganizerId = null;

        if (array_key_exists('is_global', $properties)) {
            $explicitGlobal = (bool) $properties['is_global'];
            unset($properties['is_global']);
        }

        if (array_key_exists('organizer_id', $properties)) {
            $explicitOrganizerId = $properties['organizer_id'] === null
                ? null
                : (int) $properties['organizer_id'];
            unset($properties['organizer_id']);
        }

        $activity->setAttribute('properties', collect($properties));

        return [$explicitGlobal, $explicitOrganizerId];
    }

    private function applyScope(Model $activity, ?bool $explicitGlobal, ?int $explicitOrganizerId): void
    {
        $currentTenantId = Organizer::current()?->id;
        $resolvedOrganizerId = null;
        $resolvedGlobal = false;

        if ($explicitGlobal === true) {
            if ($explicitOrganizerId !== null) {
                throw new InvalidArgumentException('is_global=true implies organizer_id IS NULL');
            }

            $resolvedGlobal = true;
        }

        if ($explicitGlobal !== true && $explicitOrganizerId !== null) {
            if ($currentTenantId !== null && $explicitOrganizerId !== $currentTenantId) {
                throw new InvalidArgumentException('Explicit organizer_id conflicts with current tenant context');
            }

            $resolvedOrganizerId = $explicitOrganizerId;
        }

        if ($explicitGlobal !== true && $explicitOrganizerId === null && $currentTenantId !== null) {
            $resolvedOrganizerId = $currentTenantId;
        }

        $activity->setAttribute('organizer_id', $resolvedOrganizerId);
        $activity->setAttribute('is_global', $resolvedGlobal);
    }

    private function ensureDefaultColumns(Model $activity): void
    {
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
    }
}
