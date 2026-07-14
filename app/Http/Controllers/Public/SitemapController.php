<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Http\Response;

final class SitemapController
{
    public function __invoke(): Response
    {
        $events = Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('visibility', EventVisibility::Public->value)
            ->oldest('starts_at')
            ->get();

        return response()
            ->view('sitemap.index', ['events' => $events])
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
