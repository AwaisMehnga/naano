<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-lg font-semibold tracking-tight text-foreground']) }}>
    <svg class="size-6 shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect width="32" height="32" rx="7" class="fill-primary" />
        <path d="M8 24V8h4.4L20 18.2V8h4v16h-4.4L12 13.8V24H8z" class="fill-primary-foreground" />
    </svg>
    {{ \Illuminate\Support\Str::title(config('app.name')) }}
</a>
