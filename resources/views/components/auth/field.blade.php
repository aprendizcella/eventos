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
    <label for="{{ $id }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        name="{{ $name }}"
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded border border-gray-400 bg-white px-3 py-2 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800']) }}
    />
    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>