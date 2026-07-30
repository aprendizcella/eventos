<?php

declare(strict_types=1);

namespace App\Livewire\Public\Tfm;

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.public')] class extends Component {
    /** @var array<int, array{title: string, description: string, defense_date: string, modified_date: string, pptx: string, pdf: string}> */
    public array $slides = [];

    public function mount(): void
    {
        $this->slides = [
            [
                'title' => 'Presentación Demo TFM Eventos',
                'description' => 'Presentación de la aplicación funcional de eventos con demostración de las funcionalidades principales: gestión de eventos, ticketing y experiencia de usuario.',
                'defense_date' => 'Julio 2026',
                'modified_date' => '29 julio 2026',
                'pptx' => 'Presentacion_Demo_TFM_Eventos.pptx',
                'pdf' => 'Presentacion_Demo_TFM_Eventos.pdf',
            ],
            [
                'title' => 'Presentación TFM Eventos Multitenant',
                'description' => 'Presentación del enfoque multitenant aplicado a la plataforma de eventos, incluyendo la arquitectura, aislamiento de datos y estrategia de despliegue.',
                'defense_date' => 'Julio 2026',
                'modified_date' => '29 julio 2026',
                'pptx' => 'Presentacion_TFM_Eventos_Multitenant.pptx',
                'pdf' => 'Presentacion_TFM_Eventos_Multitenant.pdf',
            ],
        ];
    }
};

?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Presentaciones TFM</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Diapositivas de las presentaciones del Trabajo Fin de Máster.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @foreach ($slides as $slide)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden flex flex-col">
                {{-- PDF preview --}}
                <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-800 relative overflow-hidden">
                    <iframe
                        src="{{ route('tfm.slides.download', ['file' => $slide['pdf']]) }}"
                        class="absolute inset-0 w-full h-full border-0"
                        title="{{ $slide['title'] }}"
                        loading="lazy"
                        aria-label="{{ __('PDF preview of :title', ['title' => $slide['title']]) }}"
                    ></iframe>
                </div>

                {{-- Card body --}}
                <div class="p-5 flex flex-col flex-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $slide['title'] }}</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed flex-1">
                        {{ $slide['description'] }}
                    </p>

                    {{-- Metadata --}}
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <dt class="font-medium">Fecha de defensa</dt>
                        <dd>{{ $slide['defense_date'] }}</dd>
                        <dt class="font-medium">Última modificación</dt>
                        <dd>{{ $slide['modified_date'] }}</dd>
                    </dl>

                    {{-- Download button --}}
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <a
                            href="{{ route('tfm.slides.download', ['file' => $slide['pptx']]) }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Descargar PPTX</span>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
