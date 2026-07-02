<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Actions\Tickets\CheckInAttendeeAction;
use App\Models\CheckInList;
use App\Models\Event;
use App\Services\Tickets\ValidateQrCodeService;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;
    public ?int $selectedCheckInListId = null;

    // Estado del escaneo actual para dar feedback en la interfaz
    public ?string $scanStatus = null; // success, duplicate, error
    public ?string $scanMessage = null;
    public ?string $attendeeName = null;
    public ?string $ticketType = null;
    public ?string $scannedCode = null;
    public ?string $checkedInAt = null;

    public function mount(): void
    {
        $this->authorize('viewCheckIn', $this->event);

        // Buscar lista por defecto
        $defaultList = CheckInList::query()
            ->where('event_id', $this->event->event_id)
            ->first();

        $this->selectedCheckInListId = $defaultList?->check_in_list_id;
    }

    public function processScan(string $code): void
    {
        $this->authorize('checkIn', $this->event);

        if (!$this->selectedCheckInListId) {
            $this->scanStatus = 'error';
            $this->scanMessage = __('No access point list selected.');
            return;
        }

        $this->scannedCode = $code;
        $this->checkedInAt = null;

        try {
            // 1. Ejecutar validación preventiva para capturar el estado correcto (ej. duplicados)
            $validator = resolve(ValidateQrCodeService::class);
            $validation = $validator->validate($code, $this->selectedCheckInListId);

            if (!$validation->isValid) {
                $this->scanStatus = $validation->status; // duplicate, cancelled_ticket, wrong_event, etc.
                $this->scanMessage = $validation->message;
                $this->attendeeName = $validation->attendee ? ($validation->attendee->first_name . ' ' . $validation->attendee->last_name) : null;
                $this->ticketType = $validation->attendee?->ticketOrderItem?->product?->name;
                $this->checkedInAt = $validation->checkedInAt;
                return;
            }

            // 2. Realizar check-in
            $action = resolve(CheckInAttendeeAction::class);
            $activeCheckIn = $action($code, $this->selectedCheckInListId, auth()->id());

            // Dispatch event to notify guest list component to refresh
            $this->dispatch('check-in-updated');

            $this->scanStatus = 'success';
            $this->scanMessage = __('Access granted.');
            $this->attendeeName = $activeCheckIn->attendee->first_name . ' ' . $activeCheckIn->attendee->last_name;
            $this->ticketType = $activeCheckIn->attendee->ticketOrderItem?->product?->name;
            $this->checkedInAt = null;

        } catch (\Exception $e) {
            $this->scanStatus = 'error';
            $this->scanMessage = $e->getMessage();
            $this->attendeeName = null;
            $this->ticketType = null;
            $this->checkedInAt = null;
        }
    }

    public function clearScan(): void
    {
        $this->scanStatus = null;
        $this->scanMessage = null;
        $this->attendeeName = null;
        $this->ticketType = null;
        $this->scannedCode = null;
        $this->checkedInAt = null;
    }
};
?>

<div x-data="qrScanner()" class="space-y-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('QR Code Entry Scanner') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Scan ticket QRs using device camera.') }}</p>
        </div>
        <button type="button" @click="$dispatch('close-qr-scanner')" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-gray-500 dark:text-gray-400">
            {{ __('Back to Guest List') }}
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Panel de la cámara --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-black shadow-sm dark:border-gray-800 flex flex-col items-center justify-center min-h-[300px]">
                {{-- Contenedor de la cámara con wire:ignore para evitar que Livewire destruya el canvas --}}
                <div id="qr-reader" wire:ignore class="w-full bg-black"></div>

                <div x-show="!started" class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 space-y-4 py-16 bg-black z-10">
                    <span class="text-5xl">📷</span>
                    <h4 class="text-white font-semibold">{{ __('Camera access required') }}</h4>
                    <p class="text-xs text-gray-400 max-w-xs mx-auto">{{ __('Ensure you give browser camera permissions to start scanning.') }}</p>
                    <button type="button" @click="startScanner()" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        {{ __('Start Scanning') }}
                    </button>
                </div>
            </div>

            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800/30 p-3 rounded-lg">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Access Point:') }} <strong>{{ CheckInList::query()->find($selectedCheckInListId)?->name ?? __('None') }}</strong>
                </span>
                <button type="button" x-show="started" @click="stopScanner()" class="text-xs font-semibold text-red-600 hover:text-red-500">
                    {{ __('Stop Camera') }}
                </button>
            </div>
        </div>

        {{-- Panel de Feedback del Escaneo --}}
        <div class="space-y-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Scan Result') }}</h4>

            @if($scanStatus === null)
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-800 bg-white dark:bg-gray-900 py-16">
                    <span class="text-4xl">🔲</span>
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Waiting for ticket scan...') }}
                    </p>
                </div>
            @else
                <div class="rounded-xl p-6 border shadow-sm space-y-4 {{
                    $scanStatus === 'success' ? 'bg-green-50 border-green-200 dark:bg-green-950/20 dark:border-green-900/30' : (
                    $scanStatus === 'duplicate' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950/20 dark:border-yellow-900/30' :
                    'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-900/30')
                }}">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">
                            {{ $scanStatus === 'success' ? '✅' : ($scanStatus === 'duplicate' ? '⚠️' : '❌') }}
                        </span>
                        <div>
                            <h3 class="font-bold {{
                                $scanStatus === 'success' ? 'text-green-800 dark:text-green-400' : (
                                $scanStatus === 'duplicate' ? 'text-yellow-800 dark:text-yellow-400' :
                                'text-red-800 dark:text-red-400')
                            }}">
                                {{ $scanMessage }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $scannedCode }}</p>
                        </div>
                    </div>

                    @if($attendeeName)
                        <div class="border-t border-gray-200/50 dark:border-gray-700/50 pt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <div>
                                <span class="block text-xs text-gray-400">{{ __('Attendee') }}</span>
                                <strong class="text-gray-900 dark:text-white">{{ $attendeeName }}</strong>
                            </div>
                            @if($ticketType)
                                <div>
                                    <span class="block text-xs text-gray-400">{{ __('Ticket Type') }}</span>
                                    <strong>{{ $ticketType }}</strong>
                                </div>
                            @endif
                            @if($scanStatus === 'duplicate' && $checkedInAt)
                                <div>
                                    <span class="block text-xs text-gray-400">{{ __('Scanned at') }}</span>
                                    <strong>{{ \Carbon\Carbon::parse($checkedInAt)->timezone(config('app.timezone'))->format('H:i:s d/m/Y') }}</strong>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="pt-2">
                        <button type="button" @click="clearScanResult()" class="w-full inline-flex justify-center rounded-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                            {{ __('Scan Next Ticket') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" defer></script>

<script>
    function qrScanner() {
        return {
            started: false,
            html5Qrcode: null,
            init() {
                // Escuchar evento de cierre de escaneo para detener la cámara
                window.addEventListener('close-qr-scanner', () => {
                    this.stopScanner();
                });
            },
            async startScanner() {
                if (this.started) return;

                // Asegurar que la librería se ha cargado
                if (typeof Html5Qrcode === 'undefined') {
                    alert('QR scanner library still loading. Please try again in a second.');
                    return;
                }

                try {
                    this.html5Qrcode = new Html5Qrcode("qr-reader");
                    await this.html5Qrcode.start(
                        { facingMode: "environment" },
                        {
                            fps: 15,
                            qrbox: (width, height) => {
                                const minEdge = Math.min(width, height);
                                const qrboxSize = Math.floor(minEdge * 0.75);
                                return {
                                    width: qrboxSize,
                                    height: qrboxSize
                                };
                            }
                        },
                        (decodedText) => {
                            // Cuando decodifica con éxito, detener inmediatamente el escáner y procesar
                            this.stopScanner();
                            this.$wire.processScan(decodedText);
                        },
                        (errorMessage) => {
                            // Errores de escaneo continuo se ignoran
                        }
                    );
                    this.started = true;
                } catch (err) {
                    console.error("Camera access failed:", err);
                    alert("Could not access camera: " + err);
                }
            },
            async stopScanner() {
                if (!this.started || !this.html5Qrcode) return;

                try {
                    await this.html5Qrcode.stop();
                    this.started = false;
                    this.html5Qrcode = null;
                } catch (err) {
                    console.error("Failed to stop camera:", err);
                }
            },
            clearScanResult() {
                this.$wire.clearScan();
            }
        };
    }
</script>
