@props([
    'title' => null,
    'heading' => null,
    'description' => null,
    'ajax' => false,
    'wide' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' - '.config('app.name') : config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @fonts
        @if ($ajax)
            @vite(['resources/css/app.css', 'resources/js/onboarding.js'])
        @else
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="grid min-h-svh lg:grid-cols-2">
            <div class="flex flex-col justify-center px-6 py-10 sm:px-10">
                <div class="mx-auto w-full {{ $wide ? 'max-w-xl' : 'max-w-sm' }}">
                    <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-primary">
                        {{ config('app.name') }}
                    </a>

                    @if ($heading)
                        <h1 class="mt-8 text-2xl font-medium tracking-tight">{{ $heading }}</h1>
                    @endif

                    @if ($description)
                        <p class="mt-2 text-sm text-muted-foreground">{{ $description }}</p>
                    @endif

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="hidden items-center justify-center border-l border-border bg-muted/40 p-10 lg:flex">
                <div class="w-full max-w-md">
                    {{ $panel ?? '' }}
                    @if (! isset($panel))
                        <p class="text-lg font-medium">Creators. Companies. One offer.</p>
                        <p class="mt-2 text-sm text-muted-foreground">Book a LinkedIn post at a fixed price.</p>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
