@extends('layouts.app')
@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Organizers</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage event organizers and their teams.
                </p>
            </div>
        </div>

        {{-- Organizers Table Component --}}
        <livewire:organizers.organizers-table />
    </div>
@endsection
