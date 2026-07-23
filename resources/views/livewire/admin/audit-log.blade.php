<?php

declare(strict_types=1);

use App\DataTransferObjects\Admin\AuditLogFilterDto;
use App\Models\Activity;
use App\ViewModels\Admin\AuditLogViewModel;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use AuthorizesRequests;
    use WithPagination;

    public string $draftLogName = '';

    public string $draftEvent = '';

    public string $draftDateFrom = '';

    public string $draftDateTo = '';

    public ?string $logName = null;

    public ?string $event = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $errorMessage = null;

    public function boot(): void
    {
        try {
            $this->authorize('viewAny', Activity::class);
        } catch (\Throwable $exception) {
            Log::warning('Unauthorized access attempt to global audit logs.', [
                'user_id' => auth()->id() ?? 'guest',
                'context' => 'boot',
            ]);

            throw $exception;
        }
    }

    public function applyFilters(): void
    {
        $validated = $this->validate([
            'draftLogName' => ['nullable', 'string', Rule::in(AuditLogFilterDto::LOG_NAMES)],
            'draftEvent' => ['nullable', 'string', Rule::in(AuditLogFilterDto::EVENTS)],
            'draftDateFrom' => ['nullable', 'date_format:Y-m-d', 'required_with:draftDateTo'],
            'draftDateTo' => ['nullable', 'date_format:Y-m-d', 'required_with:draftDateFrom', 'after_or_equal:draftDateFrom'],
        ]);

        $dateFrom = $this->valueOrNull($validated['draftDateFrom']);
        $dateTo = $this->valueOrNull($validated['draftDateTo']);

        if ($dateFrom !== null && $dateTo !== null && Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo), false) > 90) {
            $this->addError('draftDateTo', 'The selected date range may not exceed 90 days.');

            return;
        }

        $this->logName = $this->valueOrNull($validated['draftLogName']);
        $this->event = $this->valueOrNull($validated['draftEvent']);
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'draftLogName',
            'draftEvent',
            'draftDateFrom',
            'draftDateTo',
            'logName',
            'event',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetValidation();
        $this->resetPage();
    }

    public function with(AuditLogViewModel $viewModel): array
    {
        try {
            $this->authorize('viewAny', Activity::class);
        } catch (\Throwable $exception) {
            Log::warning('Unauthorized access attempt to global audit logs.', [
                'user_id' => auth()->id() ?? 'guest',
                'context' => 'with_render',
            ]);

            throw $exception;
        }

        try {
            $logs = $viewModel->getLogs($this->filter(), 10);
            $this->errorMessage = null;
        } catch (\Throwable) {
            $logs = new LengthAwarePaginator([], 0, 10);
            $this->errorMessage = 'Audit records are temporarily unavailable.';
        }

        return [
            'activeFilters' => $this->activeFilters(),
            'logs' => $logs,
        ];
    }

    private function filter(): AuditLogFilterDto
    {
        return new AuditLogFilterDto(
            logName: $this->logName,
            event: $this->event,
            dateFrom: $this->dateFrom === null ? null : Carbon::createFromFormat('Y-m-d', $this->dateFrom)->startOfDay(),
            dateTo: $this->dateTo === null ? null : Carbon::createFromFormat('Y-m-d', $this->dateTo)->endOfDay(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'Log' => $this->logName,
            'Event' => $this->event,
            'From' => $this->dateFrom,
            'To' => $this->dateTo,
        ]);
    }

    private function valueOrNull(?string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Global Audit Logs</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Read-only audit trail for platform security and operational events.</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">Immutable records</span>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-4">
            <div class="w-44 -mb-4">
                <x-form.select id="draftLogName" name="draftLogName" label="Log name" :options="array_combine(AuditLogFilterDto::LOG_NAMES, AuditLogFilterDto::LOG_NAMES)" placeholder="All logs" wire:model="draftLogName" />
            </div>
            <div class="w-44 -mb-4">
                <x-form.select id="draftEvent" name="draftEvent" label="Event" :options="array_combine(AuditLogFilterDto::EVENTS, AuditLogFilterDto::EVENTS)" placeholder="All events" wire:model="draftEvent" />
            </div>
            <div class="w-40 -mb-4">
                <x-form.date id="draftDateFrom" name="draftDateFrom" label="From" :value="$draftDateFrom" wire:model="draftDateFrom" />
            </div>
            <div class="w-40 -mb-4">
                <x-form.date id="draftDateTo" name="draftDateTo" label="To" :value="$draftDateTo" wire:model="draftDateTo" />
            </div>
            <div class="flex gap-2 pb-4">
                <x-ui.button type="submit" class="!w-auto">Apply filters</x-ui.button>
                @if ($activeFilters !== [])
                    <x-ui.button type="button" wire:click="resetFilters" class="!w-auto">Reset</x-ui.button>
                @endif
            </div>
        </form>
        @if ($activeFilters !== [])
            <div class="mt-4 flex flex-wrap gap-2" aria-label="Active audit filters">
                @foreach ($activeFilters as $label => $value)
                    <span wire:key="audit-filter-{{ $label }}" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-950/30 dark:text-blue-300">{{ $label }}: {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div wire:loading class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900" id="audit-log-loading">
        <div class="h-4 w-1/4 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="h-10 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
        <div class="h-10 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <div wire:loading.remove class="space-y-4">
        @if ($errorMessage !== null)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300" role="alert" id="audit-log-error-container">{{ $errorMessage }}</div>
        @else
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ trans_choice(':count matching record|:count matching records', $logs->total(), ['count' => $logs->total()]) }}</p>
            </div>
            @if ($logs->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-950/30 dark:text-gray-300" role="status" id="audit-log-empty-container">No audit records found.</div>
            @else
            <div id="audit-log-desktop-records" class="mt-4 hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">Timestamp</th>
                            <th scope="col" class="px-4 py-3">Log</th>
                            <th scope="col" class="px-4 py-3">Event</th>
                            <th scope="col" class="px-4 py-3">Description</th>
                            <th scope="col" class="px-4 py-3">Actor</th>
                            <th scope="col" class="px-4 py-3">Resource</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($logs as $log)
                            <tr wire:key="audit-log-desktop-{{ $log->id }}">
                                <td class="whitespace-nowrap px-4 py-3">{{ $log->timestamp }}</td>
                                <td class="px-4 py-3">{{ $log->logName }}</td>
                                <td class="px-4 py-3">{{ $log->event }}</td>
                                <td class="px-4 py-3">{{ $log->description }}</td>
                                <td class="px-4 py-3">{{ $log->actorName }}</td>
                                <td class="px-4 py-3">{{ $log->resourceName }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="audit-log-mobile-records" class="mt-4 grid gap-3 md:hidden">
                @foreach ($logs as $log)
                    <article wire:key="audit-log-mobile-{{ $log->id }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $log->event }}</p>
                            <time class="text-xs text-gray-500 dark:text-gray-400">{{ $log->timestamp }}</time>
                        </div>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $log->description }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <div><dt class="font-medium">Log</dt><dd>{{ $log->logName }}</dd></div>
                            <div><dt class="font-medium">Actor</dt><dd>{{ $log->actorName }}</dd></div>
                            <div class="col-span-2"><dt class="font-medium">Resource</dt><dd>{{ $log->resourceName }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
            @endif
        @endif
    </div>
</div>
