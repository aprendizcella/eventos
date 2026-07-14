<?php

declare(strict_types=1);

/**
 * @var \Illuminate\Support\Collection<int, \App\Models\Event> $events
 */
?>
@php
    echo '<?xml version="1.0" encoding="UTF-8"?>';
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($events as $event)
        <url>
            <loc>{{ route('public.events.detail', $event->slug) }}</loc>
            @if($event->starts_at)
                <lastmod>{{ $event->starts_at->toIso8601String() }}</lastmod>
            @endif
        </url>
    @endforeach
</urlset>
