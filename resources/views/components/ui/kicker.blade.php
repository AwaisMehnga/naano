@props([
    'tone' => null,
])

@php
    $classes = match ($tone) {
        'on-primary' => 'text-xs font-medium uppercase tracking-[0.18em] text-primary-foreground/65',
        'on-inverse' => 'text-xs font-medium uppercase tracking-[0.18em] text-background/65',
        default => 'text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground',
    };
@endphp

<p {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</p>
