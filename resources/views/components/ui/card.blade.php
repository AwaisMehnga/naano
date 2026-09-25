@props([
    'flush' => false,
    'href' => null,
    'selectable' => false,
])

@php
    $base = $flush
        ? 'block overflow-hidden rounded-2xl border border-border bg-card text-card-foreground'
        : 'block rounded-2xl border border-border bg-card p-6 text-card-foreground';

    if ($selectable) {
        $base .= ' transition-[border-color,background-color] has-[:checked]:border-accent has-[:checked]:bg-lime-soft';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $base]) }}>
        {{ $slot }}
    </div>
@endif
