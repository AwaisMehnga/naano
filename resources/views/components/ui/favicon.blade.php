@php
    $version = file_exists(public_path('favicon.svg'))
        ? (string) filemtime(public_path('favicon.svg'))
        : (string) time();
@endphp

<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $version }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon-32x32.png') }}?v={{ $version }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}?v={{ $version }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $version }}">
