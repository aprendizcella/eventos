<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizerResource;
use App\Models\Organizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

final class OrganizerApiController extends Controller
{
    use AuthorizesRequests;

    public function show(Organizer $organizer): OrganizerResource
    {
        $this->authorize('view', $organizer);

        return new OrganizerResource($organizer);
    }
}
