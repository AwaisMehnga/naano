<x-layouts.guest
    title="Reset password"
    heading="Reset password"
    description="Please enter your new password below"
>
    <form method="POST" action="{{ route('password.update') }}" class="grid gap-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                readonly
                autocomplete="email"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('email')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
                placeholder="Password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('password_confirmation')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            data-test="reset-password-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Reset password
        </button>
    </form>
</x-layouts.guest>
