<?php

declare(strict_types=1);

namespace App\Livewire\Organizers\Events;

use App\Actions\Waitlist\ManualExpireWaitlistEntryAction;
use App\Actions\Waitlist\NotifyWaitlistAction;
use App\Enums\WaitlistStatus;
use App\Models\Event;
use App\Models\ProductPrice;
use App\Models\WaitlistEntry;
use Spatie\Activitylog\Models\Activity;
use Livewire\Volt\Component;

new class extends Component {
    public Event $event;
    public string $filterStatus = '';
    public ?int $filterPriceId = null;

    public function notify(int $entryId, NotifyWaitlistAction $action): void
    {
        /** @var WaitlistEntry $entry */
        $entry = WaitlistEntry::query()->findOrFail($entryId);

        $action($entry);

        session()->flash('success_waitlist', __('User has been notified and invitation email queued.'));
    }

    public function expire(int $entryId, ManualExpireWaitlistEntryAction $action): void
    {
        /** @var WaitlistEntry $entry */
        $entry = WaitlistEntry::query()->findOrFail($entryId);

        $action($entry);

        session()->flash('success_waitlist', __('Waitlist entry expired manually. Next in line will be notified if automated.'));
    }

    public function with(): array
    {
        // Obtener entradas de waitlist filtradas
        $entries = WaitlistEntry::query()
            ->where('event_id', $this->event->event_id)
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterPriceId !== null, function ($query) {
                $query->where('product_price_id', $this->filterPriceId);
            })
            ->orderBy('waitlist_entry_id', 'asc')
            ->get();

        // Obtener los precios del evento para filtros
        $prices = ProductPrice::query()
            ->join('product', 'product_price.product_id', '=', 'product.product_id')
            ->where('product.event_id', $this->event->event_id)
            ->get();

        // Obtener historial de auditoría de waitlist para este evento
        $entryIds = WaitlistEntry::query()->where('event_id', $this->event->event_id)->pluck('waitlist_entry_id');

        $activities = Activity::query()
            ->where('subject_type', WaitlistEntry::class)
            ->whereIn('subject_id', $entryIds)
            ->with(['causer', 'subject'])
            ->latest()
            ->take(15)
            ->get();

        return [
            'entries' => $entries,
            'prices' => $prices,
            'activities' => $activities,
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Waitlist Management') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('View, notify, and manage attendees waiting for ticket availability.') }}
            </p>
        </div>
    </div>

    @if (session('success_waitlist'))
        <div class="rounded-lg bg-green-50 p-4 text-xs font-semibold text-green-800 dark:bg-green-950/20 dark:text-green-400">
            {{ session('success_waitlist') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 items-center bg-gray-50 p-4 rounded-xl dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800">
        <div class="w-48">
            <label class="block text-xxs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Filter by Status') }}</label>
            <select wire:model.live="filterStatus" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="waiting">{{ __('Waiting') }}</option>
                <option value="notified">{{ __('Notified') }}</option>
                <option value="reserved">{{ __('Reserved') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
                <option value="converted">{{ __('Converted') }}</option>
            </select>
        </div>

        <div class="w-64">
            <label class="block text-xxs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Filter by Ticket Price') }}</label>
            <select wire:model.live="filterPriceId" class="block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">{{ __('All Ticket Options') }}</option>
                @foreach($prices as $price)
                    <option value="{{ $price->product_price_id }}">
                        {{ $price->product->title }} - {{ $price->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Waitlist Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        @if ($entries->isEmpty())
            <div class="text-center py-12 text-gray-400 italic text-sm">
                {{ __('No entries found in the waitlist for this event.') }}
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('User') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Ticket Tier') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Notified At') }}</th>
                        <th class="px-6 py-3 text-left font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Expires At') }}</th>
                        <th class="px-6 py-3 text-right font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($entries as $entry)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $entry->first_name }} {{ $entry->last_name }}</div>
                                <div class="text-gray-400 font-medium">{{ $entry->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">
                                {{ $entry->productPrice->product->title }} — {{ $entry->productPrice->name }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = match($entry->status->value) {
                                        'waiting' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                        'notified' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'reserved' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'expired' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'converted' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 font-semibold {{ $statusClasses }}">
                                    {{ ucfirst($entry->status->value) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $entry->notified_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $entry->expires_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($entry->status->value === 'waiting')
                                        <button type="button" wire:click="notify({{ $entry->waitlist_entry_id }})" class="rounded bg-blue-600 px-2 py-1 font-bold text-white hover:bg-blue-500">
                                            {{ __('Notify') }}
                                        </button>
                                    @endif
                                    @if (in_array($entry->status->value, ['waiting', 'notified', 'reserved'], true))
                                        <button type="button" wire:click="expire({{ $entry->waitlist_entry_id }})" onsubmit="return confirm('Expire this entry manually?');" class="rounded border border-red-300 px-2 py-1 font-semibold text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/20">
                                            {{ __('Cancel') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Audit Log --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Waitlist Activity Log') }}</h4>

        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @forelse($activities as $activity)
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-800" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-xs dark:bg-gray-800">
                                        📝
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                    <div class="text-xxs text-gray-500 dark:text-gray-400 font-medium">
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            {{ $activity->causer ? $activity->causer->name : __('System') }}
                                        </span>
                                        {{ __('performed') }} <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $activity->description }}</span>
                                        {{ __('on Waitlist Entry') }} #{{ $activity->subject_id }}
                                        @if($activity->subject)
                                            ({{ $activity->subject->email }})
                                        @endif
                                    </div>
                                    <div class="text-xxs text-right whitespace-nowrap text-gray-400">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-center py-4 text-gray-400 italic text-xs">
                        {{ __('No recent activity recorded.') }}
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
