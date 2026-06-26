<?php

use function Livewire\Volt\layout;

layout('layouts.app');
?>

<div>
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Welcome to the admin panel. This is a placeholder for the dashboard.
    </p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Events</h2>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">0</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Organizers</h2>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">0</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tickets Sold</h2>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">0</p>
        </div>
    </div>
</div>
