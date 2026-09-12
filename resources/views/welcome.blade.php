<x-layouts.guest title="Welcome" heading="{{ config('app.name') }}" description="Book vetted LinkedIn creators at a fixed price per post.">
    <div class="grid gap-3">
        @auth
            <a href="{{ route('company') }}" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                Company dashboard
            </a>
            <a href="{{ route('creator') }}" class="inline-flex w-full items-center justify-center rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:opacity-90">
                Creator dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                Log in
            </a>
            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:opacity-90">
                Register
            </a>
        @endauth
    </div>
</x-layouts.guest>
