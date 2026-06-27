@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'accept' => null,
    'help' => null,
    'success' => false,
])

@php
    $id = $id ?? $name;
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
    $baseClasses = 'block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-900/30 dark:file:text-blue-300 dark:hover:file:bg-blue-900/50';
    $stateClasses = $hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
        : ($success
            ? 'border-green-400 focus:border-green-500 focus:ring-green-200'
            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200 dark:border-gray-600');
    $disabledClasses = $disabled ? 'cursor-not-allowed opacity-60' : '';
@endphp

<div class="mb-4">
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <input
        id="{{ $id }}"
        type="file"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        @if ($multiple) multiple @endif
        @if ($accept) accept="{{ $accept }}" @endif
        {{ $attributes->merge(['class' => trim("$baseClasses $stateClasses $disabledClasses")]) }}
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif
    />

    @if ($help && ! $hasError)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" id="{{ $id }}-error" />
    @endif
</div>
