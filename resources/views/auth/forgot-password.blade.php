<x-layouts.guest
    title="Forgot password"
    heading="Forgot password"
    description="Enter your email to receive a password reset link"
>
    @if ($status)
        <p class="mb-4 text-center text-sm font-medium text-primary">{{ $status }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-6">
        @csrf

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('email')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            data-test="email-password-reset-link-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Email password reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Or, return to
        <a href="{{ route('login') }}" class="text-primary underline-offset-4 hover:underline">log in</a>
    </p>
</x-layouts.guest>
