<x-layouts.guest
    title="Confirm password"
    heading="Confirm password"
    description="This is a secure area of the application. Please confirm your password before continuing."
>
    <form method="POST" action="{{ route('password.confirm.store') }}" class="grid gap-6">
        @csrf

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="Password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            data-test="confirm-password-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Confirm password
        </button>
    </form>
</x-layouts.guest>
