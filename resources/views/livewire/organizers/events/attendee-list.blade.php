<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Actions\Tickets\CheckInAttendeeAction;
use App\Actions\Tickets\UndoCheckInAction;
use App\Enums\AttendeeStatus;
use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Event $event;
    public ?int $selectedCheckInListId = null;
    public string $search = '';
    public string $statusFilter = '';

    #[On('check-in-updated')]
    public function refreshList(): void
    {
        // Se ejecuta para refrescar la lista reactivamente
    }

    public function mount(): void
    {
        $this->authorize('viewCheckIn', $this->event);

        // Asegurar que exista al menos una lista de check-in para el evento
        $defaultList = CheckInList::query()
            ->where('event_id', $this->event->event_id)
            ->firstOrCreate([
                'event_id' => $this->event->event_id,
                'name' => 'Acceso General',
                'is_active' => true,
            ]);

        $this->selectedCheckInListId = $defaultList->check_in_list_id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function manualCheckIn(string $uniqueCode): void
    {
        $this->authorize('checkIn', $this->event);

        if (!$this->selectedCheckInListId) {
            return;
        }

        try {
            $action = resolve(CheckInAttendeeAction::class);
            $action($uniqueCode, $this->selectedCheckInListId, auth()->id());
            session()->flash('success', __('Check-in registered successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function manualUndo(int $attendeeId): void
    {
        $this->authorize('undoCheckIn', $this->event);

        if (!$this->selectedCheckInListId) {
            return;
        }

        try {
            $action = resolve(UndoCheckInAction::class);
            $action($attendeeId, $this->selectedCheckInListId, auth()->id());
            session()->flash('success', __('Check-in reverted successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function with(): array
    {
        // Obtener listas del evento para el selector
        $checkInLists = CheckInList::query()
            ->where('event_id', $this->event->event_id)
            ->where('is_active', true)
            ->get();

        // Query de asistentes
        $query = Attendee::query()
            ->where('ticket_order.event_id', $this->event->event_id)
            ->join('ticket_order', 'attendee.ticket_order_id', '=', 'ticket_order.ticket_order_id')
            ->select('attendee.*')
            ->with(['ticketOrderItem.product']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('attendee.first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('attendee.last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('attendee.email', 'like', '%' . $this->search . '%')
                  ->orWhere('attendee.unique_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== '') {
            if ($this->statusFilter === 'checked_in') {
                // Filtrar los que tienen check-in activo en la lista seleccionada
                $query->whereHas('ticketOrder.event.activeCheckIns', function ($q) {
                    $q->where('active_check_in.check_in_list_id', $this->selectedCheckInListId);
                });
            } elseif ($this->statusFilter === 'active') {
                // Filtrar los que están activos pero no tienen check-in en la lista seleccionada
                $query->where('attendee.status', AttendeeStatus::Active)
                    ->whereDoesntHave('ticketOrder.event.activeCheckIns', function ($q) {
                        $q->where('active_check_in.check_in_list_id', $this->selectedCheckInListId);
                    });
            } else {
                $query->where('attendee.status', $this->statusFilter);
            }
        }

        $attendees = $query->orderBy('attendee.last_name')
            ->orderBy('attendee.first_name')
            ->paginate(15);

        // Mapear el estado del check-in activo por asistente
        $activeCheckInIds = [];
        if ($this->selectedCheckInListId) {
            $activeCheckInIds = ActiveCheckIn::query()
                ->where('check_in_list_id', $this->selectedCheckInListId)
                ->pluck('active_check_in_id', 'attendee_id')
                ->toArray();
        }

        return [
            'checkInLists' => $checkInLists,
            'attendees' => $attendees,
            'activeCheckInIds' => $activeCheckInIds,
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Notificaciones Flash --}}
    @if(session()->has('success'))
        <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950/30 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtros y selector de lista --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gray-50 p-4 rounded-xl dark:bg-gray-800/50">
        <div class="flex flex-wrap items-center gap-4">
            <div class="w-full sm:w-auto">
                <label for="check-in-list-select" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Access Point / List') }}</label>
                <select id="check-in-list-select" wire:model.live="selectedCheckInListId" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white">
                    @foreach($checkInLists as $list)
                        <option value="{{ $list->check_in_list_id }}">{{ $list->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="search-input" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Search') }}</label>
                <input id="search-input" type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Name, email or code...') }}" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white">
            </div>

            <div class="w-full sm:w-auto">
                <label for="status-filter-select" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Status') }}</label>
                <select id="status-filter-select" wire:model.live="statusFilter" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-white">
                    <option value="">{{ __('All status') }}</option>
                    <option value="active">{{ __('Active (Pending)') }}</option>
                    <option value="checked_in">{{ __('Checked In') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="button" @click="$dispatch('open-qr-scanner')" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none">
                <svg class="size-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
                {{ __('Open Camera Scanner') }}
            </button>
        </div>
    </div>

    {{-- Tabla de Asistentes --}}
    @if($attendees->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900 py-16">
            <span class="text-5xl">👥</span>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('No Attendees Found') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                {{ __('No guest matches the current search or filter criteria for this list.') }}
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __('Attendee') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Ticket') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Code') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('List Status') }}</th>
                            <th scope="col" class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($attendees as $attendee)
                            @php
                                $isCheckedIn = isset($activeCheckInIds[$attendee->attendee_id]);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $attendee->first_name }} {{ $attendee->last_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $attendee->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $attendee->ticketOrderItem->product->name ?? __('Ticket') }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-700 dark:text-gray-300">
                                    {{ $attendee->unique_code }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($attendee->status === AttendeeStatus::Cancelled)
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                            {{ __('Cancelled') }}
                                        </span>
                                    @elseif($isCheckedIn)
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-950/30 dark:text-green-400">
                                            {{ __('Checked In') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            {{ __('Pending') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($attendee->status !== AttendeeStatus::Cancelled)
                                        @if($isCheckedIn)
                                            <button type="button" wire:click="manualUndo({{ $attendee->attendee_id }})" class="text-sm font-semibold text-red-600 hover:text-red-500 dark:text-red-400">
                                                {{ __('Undo Access') }}
                                            </button>
                                        @else
                                            <button type="button" wire:click="manualCheckIn('{{ $attendee->unique_code }}')" class="text-sm font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                                {{ __('Check-In') }}
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('No Actions') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $attendees->links() }}
            </div>
        </div>
    @endif
</div>
