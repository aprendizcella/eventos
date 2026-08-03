<?php

declare(strict_types=1);

namespace App\Livewire\Public\Tfm;

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    /** @var array<int, array{title: string, description: string, embedUrl: string}> */
    public array $videos = [];

    public function mount(): void
    {
        $this->videos = [
            [
                'title' => 'Vídeo Presentación TFM',
                'description' => 'Vídeo de presentación del Trabajo Fin de Máster donde se muestran los objetivos, la arquitectura y las funcionalidades principales de la plataforma de eventos.',
                'embedUrl' => 'https://www.youtube-nocookie.com/embed/-NB4gIeLaKA',
            ],
            [
                'title' => 'Estado del Arte — Análisis con IA',
                'description' => 'Análisis del proceso de estado del arte utilizando IA ejecutada en local con Ollama. Se examinan tres proyectos de gestión de eventos realizados en Laravel, comparando sus arquitecturas y enfoques.',
                'embedUrl' => 'https://www.youtube-nocookie.com/embed/U8Cp2Z9iquE',
            ],
        ];
    }
};

?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Vídeos TFM</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Vídeos del Trabajo Fin de Máster: presentación del proyecto y análisis del estado del arte.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @foreach ($videos as $video)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden flex flex-col">
                {{-- YouTube embed --}}
                <div class="aspect-video bg-gray-100 dark:bg-gray-800 relative">
                    <iframe
                        src="{{ $video['embedUrl'] }}"
                        class="absolute inset-0 w-full h-full border-0"
                        title="{{ $video['title'] }}"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        aria-label="{{ $video['title'] }}"
                    ></iframe>
                </div>

                {{-- Card body --}}
                <div class="p-5 flex flex-col flex-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $video['title'] }}</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed flex-1">
                        {{ $video['description'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
