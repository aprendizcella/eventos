<?php

declare(strict_types=1);

namespace App\ViewModels\Public;

use App\Models\Event;
use App\ViewModels\ViewModel;

/**
 * @property-read string $title
 * @property-read string $description
 * @property-read string $canonical_url
 * @property-read array<string, string> $og_meta
 * @property-read array<string, string> $twitter_meta
 */
final class EventSeoViewModel extends ViewModel
{
    public function __construct(private readonly Event $event) {}

    public function title(): string
    {
        return $this->event->title.' — '.config('app.name');
    }

    public function description(): string
    {
        return $this->event->description !== null && $this->event->description !== ''
            ? str($this->event->description)->limit(160)->toString()
            : 'Join us for '.$this->event->title.'.';
    }

    public function canonicalUrl(): string
    {
        return route('public.events.detail', $this->event->slug);
    }

    /**
     * @return array<string, string>
     */
    public function ogMeta(): array
    {
        return [
            'title' => $this->title(),
            'description' => $this->description(),
            'url' => $this->canonicalUrl(),
            'type' => 'event',
            'site_name' => config('app.name'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function twitterMeta(): array
    {
        return [
            'card' => 'summary_large_image',
            'title' => $this->title(),
            'description' => $this->description(),
        ];
    }
}
