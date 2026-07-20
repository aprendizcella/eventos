<?php

use Livewire\Volt\Component;
use App\Models\Event;
use App\Actions\Admin\Events\SuspendEventAction;
use App\Actions\Admin\Events\RestoreEventAction;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $reason = 'Violation of terms';

    public function with(): array
    {
        return [
            'events' => Event::withoutGlobalScopes()->paginate(10),
        ];
    }

    public function suspend(Event $event, SuspendEventAction $suspendAction)
    {
        $suspendAction($event, $this->reason, auth()->user());
    }

    public function restore(Event $event, RestoreEventAction $restoreAction)
    {
        $restoreAction($event, auth()->user());
    }
}; ?>

<div>
    <h2 class="text-xl font-bold mb-4">Events</h2>
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
                <tr>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->status === \App\Enums\EventStatus::Suspended ? 'Suspended' : 'Active' }}</td>
                    <td>
                        @if($event->status === \App\Enums\EventStatus::Suspended)
                            <button wire:click="restore({{ $event->event_id }})">Restore</button>
                        @else
                            <button wire:click="suspend({{ $event->event_id }})">Suspend</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $events->links() }}
</div>
