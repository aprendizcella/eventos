@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'id' => null,
    'value' => null,
    'required' => false,
    'autofocus' => false,
    'autocomplete' => null,
])

@php
$id = $id ?? $name;
@endphp

<div class="mb-4">
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        name="{{ $name }}"
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:ring-blue-800']) }}
    />
    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
