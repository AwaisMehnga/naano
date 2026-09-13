@props([
    'title' => null,
    'heading' => null,
    'step' => 1,
    'steps' => [],
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
        @vite(['resources/css/app.css', 'resources/js/onboarding.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="grid min-h-svh lg:grid-cols-2">
            <div class="flex flex-col justify-center px-6 py-10 sm:px-10">
                <div class="mx-auto w-full max-w-xl">
                    <x-ui.logo />

                    <div class="mt-8">
                        <x-ui.stepper :step="$step" :steps="$steps" />
                        <p class="mt-3 text-sm text-muted-foreground">{{ $step }} / {{ count($steps) }}</p>
                    </div>

                    @if ($heading)
                        <h1 class="mt-6 text-2xl font-medium tracking-tight">{{ $heading }}</h1>
                    @endif

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="hidden items-center justify-center border-l border-border bg-muted p-10 lg:flex">
                <div class="w-full max-w-md">
                    {{ $panel ?? '' }}
                </div>
            </div>
        </div>
    </body>
</html>
