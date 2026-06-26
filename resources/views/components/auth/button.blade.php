@props([
    'type' => 'submit',
])

<button {{ $attributes->merge(['type' => $type, 'class' => 'w-full rounded bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2']) }}>
    {{ $slot }}
</button>