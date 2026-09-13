@props([
    'variant' => 'info',
])

@php
    $classes = match ($variant) {
        'success' => 'text-sm font-medium text-accent',
        'danger' => 'text-sm text-destructive',
        default => 'text-sm text-foreground',
    };
@endphp

<p {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</p>
