@props([
    'title' => null,
    'kicker' => null,
    'heading' => null,
    'description' => null,
    'wide' => false,
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
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="bg-background">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-4">
                <x-ui.logo />
                <div class="flex items-center gap-2">
                    @guest
                        @if (request()->routeIs('login', 'password.request', 'password.reset', 'two-factor.login'))
                            <x-ui.button href="{{ route('register') }}">Get started</x-ui.button>
                        @else
                            <x-ui.button href="{{ route('login') }}" variant="ghost">Log in</x-ui.button>
                        @endif
                    @endguest
                </div>
            </div>
        </header>

        <div class="grid min-h-[calc(100svh-4.5rem)] lg:grid-cols-2">
            <div class="flex flex-col justify-center px-6 py-12 sm:px-10">
                <div class="mx-auto w-full {{ $wide ? 'max-w-xl' : 'max-w-sm' }}">
                    @if ($kicker)
                        <x-ui.kicker>{{ $kicker }}</x-ui.kicker>
                    @endif

                    @if ($heading)
                        <h1 @class(['text-4xl font-normal tracking-tight', 'mt-6' => (bool) $kicker, 'mt-2' => ! $kicker])>
                            {{ $heading }}
                        </h1>
                    @endif

                    @if ($description)
                        <p class="mt-4 text-lg leading-relaxed text-muted-foreground">{{ $description }}</p>
                    @endif

                    <div class="mt-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="hidden items-center px-10 py-16 lg:flex">
                <div class="w-full max-w-md">
                    {{ $panel ?? '' }}
                    @if (! isset($panel))
                        <x-ui.kicker>Naano</x-ui.kicker>
                        <p class="mt-6 text-4xl font-normal tracking-tight">
                            Book vetted LinkedIn
                            <x-ui.em>creators</x-ui.em>
                            at a fixed price.
                        </p>
                    @endif
                </div>
            </div>
        </div>
        <script>
            document.querySelectorAll('form[method="post"], form[method="POST"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.busy === '1') {
                        event.preventDefault();

                        return;
                    }

                    form.dataset.busy = '1';
                    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                        button.disabled = true;
                    });
                });
            });
        </script>
    </body>
</html>
