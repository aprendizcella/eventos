@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'autocomplete' => null,
    'placeholder' => null,
    'disabled' => false,
    'help' => null,
    'success' => false,
])

@php
    $id = $id ?? $name;
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
    $baseClasses = 'block w-full rounded-lg border px-3 py-2 pr-11 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-0';
    $stateClasses = $hasError
        ? 'border-red-400 bg-white text-gray-900 focus:border-red-500 focus:ring-red-200 dark:border-red-500 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-red-800'
        : ($success
            ? 'border-green-400 bg-white text-gray-900 focus:border-green-500 focus:ring-green-200 dark:border-green-500 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-green-800'
            : 'border-gray-300 bg-white text-gray-900 focus:border-blue-500 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-800');
    $disabledClasses = $disabled ? 'cursor-not-allowed opacity-60 bg-gray-100 dark:bg-gray-900' : '';
@endphp

<div class="mb-4" x-data="{ shown: false }">
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div class="relative">
        <input
            id="{{ $id }}"
            :type="shown ? 'text' : 'password'"
            name="{{ $name }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => trim("$baseClasses $stateClasses $disabledClasses")]) }}
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
        />
        <button
            type="button"
            @click="shown = !shown"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            :aria-label="shown ? 'Hide password' : 'Show password'"
            aria-controls="{{ $id }}"
            @if ($disabled) disabled @endif
        >
            <svg x-show="!shown" class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
            <svg x-show="shown" x-cloak class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </button>
    </div>

    @if ($help && ! $hasError)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" id="{{ $id }}-error" />
    @endif
</div>
