@props(['label' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-4']) }}>
    @if($label)
        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 w-full">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
