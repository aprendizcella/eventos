<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\DataTransferObjects\Organizers\UpdateOrganizerDto;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOrganizerAction
{
    public function __invoke(Organizer $organizer, UpdateOrganizerDto $dto, User $updater): Organizer
    {
        return DB::transaction(function () use ($organizer, $dto, $updater): Organizer {
            $data = [
                'name' => $dto->name,
                'slug' => $dto->slug,
            ];

            if ($dto->domain === '') {
                $data['domain'] = null;
            } elseif ($dto->domain !== null) {
                $data['domain'] = $dto->domain;
            }

            if ($dto->settings !== null) {
                $data['settings'] = $dto->settings;
            }

            if ($dto->status !== null) {
                $data['status'] = $dto->status;
            }

            $organizer->update($data);

            activity()
                ->performedOn($organizer)
                ->causedBy($updater)
                ->log('updated');

            return $organizer->refresh();
        });
    }
}
