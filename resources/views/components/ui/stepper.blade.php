@props([
    'step' => 1,
    'steps' => [],
])

<ol {{ $attributes->merge(['class' => 'flex flex-wrap gap-3']) }}>
    @foreach ($steps as $index => $label)
        @php
            $number = $index + 1;
        @endphp
        <li class="flex items-center gap-2 text-sm">
            <span @class([
                'inline-flex size-6 items-center justify-center rounded-full text-xs font-medium',
                'bg-primary text-primary-foreground' => $number === $step,
                'bg-primary/80 text-primary-foreground' => $number < $step,
                'bg-muted text-muted-foreground' => $number > $step,
            ])>{{ $number }}</span>
            <span @class([
                'text-foreground' => $number === $step,
                'text-muted-foreground' => $number !== $step,
            ])>{{ $label }}</span>
        </li>
    @endforeach
</ol>
