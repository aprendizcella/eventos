@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'help' => null,
    'name' => null,
    'success' => false,
])

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $name ? $errors->has($name) : false;
@endphp

<div class="mb-4">
    @if ($label)
        <x-form.label :for="$for" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div class="flex">
        @if (isset($prefix))
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                {{ $prefix }}
            </span>
        @endif

        <div class="{{ isset($prefix) || isset($suffix) ? 'flex-1' : 'w-full' }}">
            {{ $slot }}
        </div>

        @if (isset($suffix))
            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if ($help && ! $hasError)
        <x-form.help>{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" />
    @endif
</div>
