@props([
    'step' => 1,
    'steps' => [],
])

<ol {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    @foreach ($steps as $index => $label)
        @php
            $number = $index + 1;
            $done = $number < $step;
            $current = $number === $step;
        @endphp
        <li @class([
            'inline-flex items-center gap-2 rounded-pill px-3 py-1.5 text-xs font-medium',
            'bg-primary text-primary-foreground' => $current,
            'bg-lime-soft text-foreground' => $done,
            'bg-muted text-muted-foreground' => ! $current && ! $done,
        ])>
            <span @class([
                'inline-flex size-5 items-center justify-center rounded-full text-[10px]',
                'bg-primary-foreground/20' => $current,
                'bg-accent/40' => $done,
                'bg-card' => ! $current && ! $done,
            ])>{{ $number }}</span>
            <span>{{ $label }}</span>
        </li>
    @endforeach
</ol>
