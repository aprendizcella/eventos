@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'help' => null,
    'success' => false,
])

@php
    $id = $id ?? $name;
    $selectedValue = old($name, $selected);
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
    $baseClasses = 'block w-full appearance-none rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 dark:bg-gray-800 dark:text-gray-100';
    $stateClasses = $hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:border-red-500 dark:focus:ring-red-800'
        : ($success
            ? 'border-green-400 focus:border-green-500 focus:ring-green-200 dark:border-green-500 dark:focus:ring-green-800'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200 dark:border-gray-600 dark:focus:ring-blue-800');
    $disabledClasses = $disabled ? 'cursor-not-allowed bg-gray-100 opacity-60 dark:bg-gray-900' : '';
@endphp

<div class="mb-4">
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($multiple) multiple @endif
            {{ $attributes->merge(['class' => trim("$baseClasses $stateClasses $disabledClasses pr-10")]) }}
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $label)
                <option value="{{ $value }}" @selected((string) $selectedValue === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 dark:text-gray-400">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </span>
    </div>

    @if ($help && ! $hasError)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" id="{{ $id }}-error" />
    @endif
</div>
