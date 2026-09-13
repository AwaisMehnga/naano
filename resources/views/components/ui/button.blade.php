@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = match ($variant) {
        'secondary' => 'inline-flex items-center justify-center rounded-sm border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground hover:opacity-90 disabled:opacity-50',
        'inverted' => 'inline-flex items-center justify-center rounded-sm bg-primary-foreground px-4 py-2.5 text-sm font-medium text-primary hover:opacity-90 disabled:opacity-50',
        'ghost' => 'inline-flex items-center justify-center rounded-sm bg-transparent px-4 py-2.5 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50',
        'link' => 'inline-flex items-center justify-center rounded-sm bg-transparent px-0 py-2.5 text-sm font-medium text-primary underline-offset-4 hover:underline disabled:opacity-50',
        default => 'inline-flex items-center justify-center rounded-sm bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50',
    };

    if ($size === 'lg') {
        $classes .= ' px-5 py-3';
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
