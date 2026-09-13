@props([
    'flush' => false,
    'href' => null,
])

@php
    $classes = $flush
        ? 'block overflow-hidden rounded-lg border border-border bg-card text-card-foreground'
        : 'block rounded-lg border border-border bg-card p-6 text-card-foreground';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </div>
@endif
