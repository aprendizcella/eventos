<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] class extends Component {
    //
}; ?>

<div>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">
                Admin Dashboard
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                <p>Welcome to the platform administration backoffice.</p>
            </div>
        </div>
    </div>
</div>
