@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'accept' => null,
    'help' => null,
    'prompt' => 'Click to upload or drag and drop',
    'hint' => null,
])

@php
    $id = $id ?? $name;
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $hasError = $errors->has($name);
@endphp

<div
    class="mb-4"
    x-data="{
        dragging: false,
        files: [],
        updateFromInput(input) {
            this.files = Array.from(input.files);
        }
    }"
>
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif

    <label
        for="{{ $id }}"
        class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-8 text-center transition-colors"
        :class="dragging
            ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
            : '{{ $hasError ? 'border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/10' : 'border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:hover:bg-gray-800' }}'"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="dragging = false; files = Array.from($event.dataTransfer.files)"
    >
        <svg class="mb-3 size-8 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $prompt }}</span>
        </p>
        @if ($hint)
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">{{ $hint }}</p>
        @endif
        <template x-if="files.length">
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="files.map(f => f.name).join(', ')"></p>
        </template>
        <input
            id="{{ $id }}"
            type="file"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            class="sr-only"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($multiple) multiple @endif
            @if ($accept) accept="{{ $accept }}" @endif
            @change="updateFromInput($event.target)"
            @if ($hasError) aria-invalid="true" @endif
        />
    </label>

    @if ($help && ! $hasError)
        <x-form.help>{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" />
    @endif
</div>
