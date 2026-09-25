@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = match ($variant) {
        'secondary' => 'inline-flex items-center justify-center rounded-pill border border-border bg-card px-6 py-2.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50',
        'accent' => 'inline-flex items-center justify-center rounded-pill bg-accent px-6 py-2.5 text-sm font-medium text-accent-foreground hover:opacity-90 disabled:opacity-50',
        'inverted' => 'inline-flex items-center justify-center rounded-pill bg-card px-6 py-2.5 text-sm font-medium text-foreground border border-border hover:bg-muted disabled:opacity-50',
        'ghost' => 'inline-flex items-center justify-center rounded-pill bg-transparent px-6 py-2.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50',
        'link' => 'inline-flex items-center justify-center rounded-none bg-transparent px-0 py-2.5 text-sm font-medium text-foreground underline-offset-4 hover:underline disabled:opacity-50',
        default => 'inline-flex items-center justify-center rounded-pill bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50',
    };

    if ($size === 'lg') {
        $classes .= ' px-7 py-3 text-base';
    }

    if ($size === 'sm') {
        $classes .= ' h-10 px-5 text-sm';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
