@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = match ($variant) {
        'secondary' => 'inline-flex items-center justify-center rounded-pill border border-border bg-card px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50',
        'accent' => 'inline-flex items-center justify-center rounded-pill bg-accent px-5 py-2.5 text-sm font-medium text-accent-foreground hover:opacity-90 disabled:opacity-50',
        'inverted' => 'inline-flex items-center justify-center rounded-pill bg-card px-5 py-2.5 text-sm font-medium text-foreground border border-border hover:bg-muted disabled:opacity-50',
        'ghost' => 'inline-flex items-center justify-center rounded-pill bg-transparent px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50',
        'link' => 'inline-flex items-center justify-center rounded-none bg-transparent px-0 py-2.5 text-sm font-medium text-foreground underline-offset-4 hover:underline disabled:opacity-50',
        default => 'inline-flex items-center justify-center rounded-pill bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50',
    };

    if ($size === 'lg') {
        $classes .= ' px-6 py-3';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
