@props([
    'type' => 'text',
])

<input
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'w-full rounded-pill border border-input bg-card px-4 py-2.5 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30']) }}
>
