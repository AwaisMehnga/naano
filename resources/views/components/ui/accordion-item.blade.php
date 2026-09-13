@props([
    'question',
    'name' => null,
    'open' => false,
    'tone' => null,
])

@php
    $border = match ($tone) {
        'on-inverse' => 'border-background/15',
        'on-primary' => 'border-primary-foreground/15',
        default => 'border-border',
    };
    $body = match ($tone) {
        'on-inverse' => 'text-background/75',
        'on-primary' => 'text-primary-foreground/75',
        default => 'text-muted-foreground',
    };
    $icon = match ($tone) {
        'on-inverse' => 'text-background/50',
        'on-primary' => 'text-primary-foreground/50',
        default => 'text-muted-foreground',
    };
@endphp

<details
    @if ($name) name="{{ $name }}" @endif
    @if ($open) open @endif
    {{ $attributes->merge(['class' => 'group border-t '.$border.' last:border-b']) }}
>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-4 text-lg marker:content-none [&::-webkit-details-marker]:hidden">
        {{ $question }}
        <span class="{{ $icon }} group-open:hidden">+</span>
        <span class="hidden {{ $icon }} group-open:inline">−</span>
    </summary>
    <p class="max-w-3xl pb-4 text-sm leading-relaxed {{ $body }}">
        {{ $slot }}
    </p>
</details>
