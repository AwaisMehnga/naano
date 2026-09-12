@props([
    'title',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} - {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 py-10">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-primary">
                        <a href="{{ route('company') }}" class="hover:underline">Back to dashboard</a>
                    </p>
                    <h1 class="text-2xl font-semibold">Settings</h1>
                    <p class="text-sm text-muted-foreground">Manage your profile and account settings</p>
                </div>
            </div>

            <div class="flex flex-col gap-8 md:flex-row">
                <nav class="flex gap-2 md:w-48 md:flex-col">
                    <a href="{{ route('profile.edit') }}" @class(['rounded-md px-3 py-2 text-sm', 'bg-secondary font-medium' => request()->routeIs('profile.edit'), 'text-muted-foreground hover:text-foreground' => ! request()->routeIs('profile.edit')])>Profile</a>
                    <a href="{{ route('security.edit') }}" @class(['rounded-md px-3 py-2 text-sm', 'bg-secondary font-medium' => request()->routeIs('security.edit'), 'text-muted-foreground hover:text-foreground' => ! request()->routeIs('security.edit')])>Security</a>
                    <a href="{{ route('appearance.edit') }}" @class(['rounded-md px-3 py-2 text-sm', 'bg-secondary font-medium' => request()->routeIs('appearance.edit'), 'text-muted-foreground hover:text-foreground' => ! request()->routeIs('appearance.edit')])>Appearance</a>
                </nav>
                <div class="min-w-0 flex-1">
                    @if (session('status'))
                        <p class="mb-4 text-sm font-medium text-primary">{{ session('status') }}</p>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
