<?php

use Livewire\Volt\Component;
use App\ViewModels\Admin\AuditLogViewModel;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;
    use AuthorizesRequests;

    public ?string $errorMessage = null;

    public function boot(): void
    {
        // Fail closed immediately at the component level using Policy check
        try {
            $this->authorize('viewAny', \App\Models\Activity::class);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized access attempt to global audit logs.', [
                'user_id' => auth()->id() ?? 'guest',
                'context' => 'boot',
            ]);
            throw $e;
        }
    }

    public function with(AuditLogViewModel $viewModel): array
    {
        // Fail closed on any subsequent render context if the user's role has been revoked
        try {
            $this->authorize('viewAny', \App\Models\Activity::class);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized access attempt to global audit logs.', [
                'user_id' => auth()->id() ?? 'guest',
                'context' => 'with_render',
            ]);
            throw $e;
        }

        try {
            $logs = $viewModel->getLogs(10);
            $this->errorMessage = null;
        } catch (\Throwable) {
            $logs = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $this->errorMessage = 'A database query failure occurred during audit presentation.';
        }

        return [
            'logs' => $logs,
        ];
    }
}; ?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Global Audit Logs</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Read-only platform security log records.</p>
    </div>

    <!-- Loading Skeleton indicator -->
    <div wire:loading class="w-full space-y-4 mb-4" id="audit-log-loading">
        <div class="h-4 bg-gray-200 rounded dark:bg-gray-700 w-1/4 animate-pulse"></div>
        <div class="h-10 bg-gray-200 rounded dark:bg-gray-700 w-full animate-pulse"></div>
        <div class="h-10 bg-gray-200 rounded dark:bg-gray-700 w-full animate-pulse"></div>
    </div>

    <div wire:loading.remove>
        @if($errorMessage)
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert" id="audit-log-error-container">
                {{ $errorMessage }}
            </div>
        @elseif($logs->isEmpty())
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert" id="audit-log-empty-container">
                No audit records found.
            </div>
        @else
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Timestamp</th>
                            <th scope="col" class="px-6 py-3">Log Name</th>
                            <th scope="col" class="px-6 py-3">Event</th>
                            <th scope="col" class="px-6 py-3">Description</th>
                            <th scope="col" class="px-6 py-3">Actor</th>
                            <th scope="col" class="px-6 py-3">Resource</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr wire:key="audit-log-{{ $log->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{ $log->timestamp }}</td>
                                <td class="px-6 py-4">{{ $log->logName }}</td>
                                <td class="px-6 py-4">{{ $log->event }}</td>
                                <td class="px-6 py-4">{{ $log->description }}</td>
                                <td class="px-6 py-4">{{ $log->actorName }}</td>
                                <td class="px-6 py-4">{{ $log->resourceName }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
