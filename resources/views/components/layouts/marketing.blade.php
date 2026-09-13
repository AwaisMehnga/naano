@props([
    'title' => null,
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
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/landing.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="sticky top-0 z-10 bg-background">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-5">
                <x-ui.logo />
                <nav class="hidden items-center gap-8 text-sm md:flex">
                    <a href="#how-it-works" class="text-foreground">How it works</a>
                    <a href="#companies" class="text-foreground">Companies</a>
                    <a href="#creators" class="text-foreground">Creators</a>
                    <a href="#pricing" class="text-foreground">Pricing</a>
                </nav>
                <div class="flex items-center gap-2">
                    @auth
                        <x-ui.button href="{{ \App\Support\HomeRedirect::path(auth()->user()) }}">
                            Go to workspace
                        </x-ui.button>
                    @else
                        <x-ui.button href="{{ route('login') }}" variant="ghost">Log in</x-ui.button>
                        <x-ui.button href="{{ route('register') }}">Get started</x-ui.button>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="bg-primary text-primary-foreground">
            <div class="mx-auto grid max-w-6xl gap-10 px-6 py-16 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-3">
                    <x-ui.kicker tone="on-primary">Product</x-ui.kicker>
                    <a href="#how-it-works" class="text-sm text-primary-foreground">How it works</a>
                    <a href="#pricing" class="text-sm text-primary-foreground">Pricing</a>
                    <a href="{{ route('register') }}" class="text-sm text-primary-foreground">Get started</a>
                </div>
                <div class="grid gap-3">
                    <x-ui.kicker tone="on-primary">Companies</x-ui.kicker>
                    <a href="#companies" class="text-sm text-primary-foreground">Book creators</a>
                    <a href="{{ route('register.company') }}" class="text-sm text-primary-foreground">Create a company account</a>
                    <a href="{{ route('login') }}" class="text-sm text-primary-foreground">Log in</a>
                </div>
                <div class="grid gap-3">
                    <x-ui.kicker tone="on-primary">Creators</x-ui.kicker>
                    <a href="#creators" class="text-sm text-primary-foreground">Get booked</a>
                    <a href="{{ route('register.creator') }}" class="text-sm text-primary-foreground">Join as a creator</a>
                </div>
                <div class="grid gap-3">
                    <x-ui.kicker tone="on-primary">{{ config('app.name') }}</x-ui.kicker>
                    <p class="text-sm text-primary-foreground/65">The B2B LinkedIn creator marketplace.</p>
                </div>
            </div>
            <div>
                <p class="mx-auto max-w-6xl px-6 py-6 text-xs tracking-wide text-primary-foreground/65">
                    &copy; {{ now()->year }} {{ config('app.name') }}. The B2B LinkedIn creator marketplace.
                </p>
            </div>
        </footer>
    </body>
</html>
