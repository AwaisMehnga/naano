<x-layouts.guest
    title="Verify email"
    heading="Check your email"
    description="Enter the 6-digit code we sent you."
>
    @if ($status === 'verification-link-sent')
        <p class="mb-4 text-sm font-medium text-primary">A new code is on its way.</p>
    @endif

    <form method="POST" action="{{ route('verification.code') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-2">
            <label for="code" class="text-sm font-medium">Code</label>
            <input
                id="code"
                type="text"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                autofocus
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-center text-lg tracking-[0.4em] shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('code')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Verify
        </button>
    </form>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-sm text-primary underline-offset-4 hover:underline">
            Resend code
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-muted-foreground underline-offset-4 hover:underline">
            Log out
        </button>
    </form>

    <x-slot:panel>
        <p class="text-lg font-medium">Almost in.</p>
        <p class="mt-2 text-sm text-muted-foreground">Verify once. Then we finish your profile.</p>
    </x-slot:panel>
</x-layouts.guest>
