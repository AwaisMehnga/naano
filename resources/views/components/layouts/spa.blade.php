@props([
    'title',
    'entry',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} - {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600" rel="stylesheet" />
        @viteReactRefresh
        @vite(['resources/css/app.css', $entry])
    </head>
    <body class="min-h-screen bg-background font-dashboard text-foreground antialiased">
        @php
            $user = auth()->user();
            $naano = [
                'name' => config('app.name'),
                'user' => $user === null
                    ? null
                    : app(\App\Services\UserService::class)->current($user, request()),
            ];
        @endphp
        <script>
            window.Naano = @json($naano);
        </script>
        <div id="app"></div>
    </body>
</html>
