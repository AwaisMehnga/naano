<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'text-lg font-semibold tracking-tight text-foreground']) }}>
    {{ \Illuminate\Support\Str::title(config('app.name')) }}
</a>
