<?php

declare(strict_types=1);

namespace App\Livewire\Public\Tfm;

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    public string $videoTitle = 'Video Presentación TFM';
    public string $videoDescription = 'Vídeo de presentación del Trabajo Fin de Máster donde se muestran los objetivos, la arquitectura y las funcionalidades principales de la plataforma de eventos.';
    public string $embedUrl = 'https://www.youtube-nocookie.com/embed/-NB4gIeLaKA';
};

?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Vídeo Presentación TFM</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Vídeo de presentación del Trabajo Fin de Máster.
        </p>
    </div>

    <div class="max-w-3xl">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            {{-- YouTube embed --}}
            <div class="aspect-video bg-gray-100 dark:bg-gray-800 relative">
                <iframe
                    src="{{ $embedUrl }}"
                    class="absolute inset-0 w-full h-full border-0"
                    title="{{ $videoTitle }}"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    aria-label="{{ $videoTitle }}"
                ></iframe>
            </div>

            {{-- Card body --}}
            <div class="p-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $videoTitle }}</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ $videoDescription }}
                </p>
            </div>
        </div>
    </div>
</div>
