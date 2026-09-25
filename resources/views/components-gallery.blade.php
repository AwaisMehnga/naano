<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Components - {{ config('app.name') }}</title>
        <x-ui.favicon />
        @fonts
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/components-gallery.tsx'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div id="app"></div>
    </body>
</html>
