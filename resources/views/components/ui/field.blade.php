@props([
    'label' => null,
    'name' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'grid gap-2']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="text-sm font-medium">{{ $label }}</label>
    @endif

    @if ($hint)
        <p class="text-sm text-muted-foreground">{{ $hint }}</p>
    @endif

    {{ $slot }}

    @if ($name)
        <p
            data-error-for="{{ $name }}"
            @class(['text-sm text-destructive', 'hidden' => ! $errors->has($name)])
        >
            @error($name)
                {{ $message }}
            @enderror
        </p>
    @endif
</div>
