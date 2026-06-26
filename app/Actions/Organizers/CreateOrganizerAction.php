<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\DataTransferObjects\Organizers\CreateOrganizerDto;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrganizerAction
{
    public function __invoke(CreateOrganizerDto $dto, User $creator): Organizer
    {
        return DB::transaction(function () use ($dto, $creator): Organizer {
            $organizer = Organizer::query()->create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'domain' => $dto->domain,
                'settings' => $dto->settings,
                'status' => $dto->status,
            ]);

            activity()
                ->performedOn($organizer)
                ->causedBy($creator)
                ->log('created');

            return $organizer;
        });
    }
}
