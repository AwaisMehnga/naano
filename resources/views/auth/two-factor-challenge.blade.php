@php
    $recovery = request()->boolean('recovery');
@endphp

<x-layouts.guest
    title="Two-factor authentication"
    heading="{{ $recovery ? 'Recovery code' : 'Authentication code' }}"
    description="{{ $recovery
        ? 'Please confirm access to your account by entering one of your emergency recovery codes.'
        : 'Enter the authentication code provided by your authenticator application.' }}"
>
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="grid gap-4">
        @csrf

        @if ($recovery)
            <input
                type="text"
                name="recovery_code"
                required
                autofocus
                placeholder="Enter recovery code"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('recovery_code')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        @else
            <input
                type="text"
                name="code"
                required
                autofocus
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="Authentication code"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-center text-sm tracking-widest shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('code')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        @endif

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Continue
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-muted-foreground">
        or you can
        <a
            href="{{ route('two-factor.login', $recovery ? [] : ['recovery' => 1]) }}"
            class="text-foreground underline underline-offset-4"
        >
            {{ $recovery ? 'login using an authentication code' : 'login using a recovery code' }}
        </a>
    </p>
</x-layouts.guest>
