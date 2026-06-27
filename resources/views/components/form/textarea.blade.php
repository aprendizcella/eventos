@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'rows' => 4,
    'help' => null,
    'success' => false,
])

@php
    $id = $id ?? $name;
    $value = old($name, $value);
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
    $baseClasses = 'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-0';
    $stateClasses = $hasError
        ? 'border-red-400 bg-white text-gray-900 focus:border-red-500 focus:ring-red-200 dark:border-red-500 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-red-800'
        : ($success
            ? 'border-green-400 bg-white text-gray-900 focus:border-green-500 focus:ring-green-200 dark:border-green-500 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-green-800'
            : 'border-gray-300 bg-white text-gray-900 focus:border-blue-500 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-800');
    $disabledClasses = $disabled ? 'cursor-not-allowed opacity-60 bg-gray-100 dark:bg-gray-900' : '';
@endphp

<div class="mb-4">
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        @if ($readonly) readonly @endif
        {{ $attributes->merge(['class' => trim("$baseClasses $stateClasses $disabledClasses")]) }}
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
    >{{ $value }}</textarea>

    @if ($help && ! $hasError)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" id="{{ $id }}-error" />
    @endif
</div>
