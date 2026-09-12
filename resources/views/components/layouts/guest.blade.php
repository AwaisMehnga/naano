@props([
    'title' => null,
    'heading' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title.' - '.config('app.name') : config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex flex-col items-center gap-3 text-center">
                    <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-primary">
                        {{ config('app.name') }}
                    </a>
                    @if ($heading)
                        <h1 class="text-xl font-medium">{{ $heading }}</h1>
                    @endif
                    @if ($description)
                        <p class="text-sm text-muted-foreground">{{ $description }}</p>
                    @endif
                </div>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
