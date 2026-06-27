@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'help' => null,
    'onLabel' => 'On',
    'offLabel' => 'Off',
])

@php
    $id = $id ?? $name;
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $isChecked = old($name, $checked) ? true : false;
    $hasError = $errors->has($name);
@endphp

<div
    class="mb-4"
    x-data="{
        on: @js($isChecked),
        fieldName: @js($name),
        fieldValue: @js($value),
        onLabel: @js($onLabel),
        offLabel: @js($offLabel),
    }"
>
    @if ($label)
        <x-form.label :for="$id">{{ $label }}</x-form.label>
    @endif

    <div class="flex items-center gap-3">
        <button
            id="{{ $id }}"
            type="button"
            role="switch"
            :aria-checked="on.toString()"
            @click="on = !on"
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed disabled:opacity-60']) }}
            :class="on ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'"
        >
            <span
                class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition"
                :class="on ? 'translate-x-5' : 'translate-x-0'"
                aria-hidden="true"
            ></span>
        </button>
        <input type="hidden" :name="on ? fieldName : null" :value="fieldValue" />
        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="on ? onLabel : offLabel"></span>
    </div>

    @if ($help)
        <x-form.help>{{ $help }}</x-form.help>
    @endif

    @if ($hasError)
        <x-form.error :name="$name" />
    @endif
</div>
