<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Actions\Admin\Users\SuspendUserAction;
use App\Actions\Admin\Users\RestoreUserAction;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'users' => User::query()->paginate(10),
        ];
    }

    public function suspend(User $user, SuspendUserAction $suspendAction)
    {
        $suspendAction($user);
    }

    public function restore(User $user, RestoreUserAction $restoreAction)
    {
        $restoreAction($user);
    }
}; ?>

<div>
    <h2 class="text-xl font-bold mb-4">Users</h2>
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->isSuspended() ? 'Suspended' : 'Active' }}</td>
                    <td>
                        @if($user->isSuspended())
                            <button wire:click="restore({{ $user->id }})">Restore</button>
                        @else
                            <button wire:click="suspend({{ $user->id }})">Suspend</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $users->links() }}
</div>
