@props([
    'title' => null,
    'kicker' => null,
    'heading' => null,
    'description' => null,
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
        <x-ui.favicon />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/onboarding.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="flex min-h-screen flex-col">
                <header class="border-b border-border bg-background">
                    <div class="flex items-center justify-between gap-4 px-6 py-4 sm:px-10">
                        <x-ui.logo />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">Log out</x-ui.button>
                        </form>
                    </div>
                </header>

                <main class="flex flex-1 flex-col justify-center px-6 py-10 sm:px-10 lg:py-14">
                    <div class="mx-auto w-full max-w-lg">
                        @if ($steps !== [])
                            <x-ui.stepper :step="$step" :steps="$steps" class="mb-8" />
                        @endif

                        @if ($kicker)
                            <x-ui.kicker>{{ $kicker }}</x-ui.kicker>
                        @endif

                        @if ($heading)
                            <h1 @class([
                                'text-heading font-medium tracking-tight text-balance',
                                'mt-4' => (bool) $kicker || $steps !== [],
                            ])>
                                {{ $heading }}
                            </h1>
                        @endif

                        @if ($description)
                            <p class="mt-3 text-body leading-relaxed text-foreground/80">{{ $description }}</p>
                        @endif

                        <div class="mt-8">
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>

            <aside class="hidden flex-col justify-between bg-primary px-10 py-10 text-primary-foreground lg:flex xl:px-14 xl:py-12">
                <div>
                    <x-ui.logo tone="on-primary" />
                </div>
                <div class="max-w-md">
                    @isset($panel)
                        {{ $panel }}
                    @else
                        <x-ui.kicker tone="on-primary">Setup</x-ui.kicker>
                        <p class="mt-5 text-title font-medium tracking-tight">Finish your profile.</p>
                        <p class="mt-4 text-body leading-relaxed text-primary-foreground/80">
                            A few steps, then your workspace opens.
                        </p>
                    @endisset
                </div>
                <p class="text-sm text-primary-foreground/60">
                    {{ config('app.name') }} · Onboarding
                </p>
            </aside>
        </div>
    </body>
</html>
