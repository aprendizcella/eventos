<?php

declare(strict_types=1);

namespace App\Actions\Organizers;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteOrganizerAction
{
    public function __invoke(Organizer $organizer, User $deleter): void
    {
        DB::transaction(function () use ($organizer, $deleter): void {
            $organizer->update(['status' => 'inactive']);
            $organizer->delete();

            activity()
                ->performedOn($organizer)
                ->causedBy($deleter)
                ->log('deleted');
        });
    }
}
