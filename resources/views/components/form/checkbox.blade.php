@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'help' => null,
])

@php
    $id = $id ?? $name;
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $isChecked = old($name, $checked) ? true : false;
    $hasError = $errors->has($name);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="inline-flex cursor-pointer items-start gap-3">
        <input
            id="{{ $id }}"
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            @if ($isChecked) checked @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'mt-0.5 size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:checked:border-blue-500']) }}
            @if ($hasError) aria-invalid="true" @endif
        />
        <span class="text-sm text-gray-700 dark:text-gray-300">
            {{ $label ?? $slot }}
        </span>
    </label>

    @if ($help)
        <x-form.help>{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" />
    @endif
</div>
