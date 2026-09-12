<x-layouts.guest
    title="Log in"
    heading="Log in to your account"
    description="Enter your email and password below to log in"
>
    @if ($status)
        <p class="mb-4 text-center text-sm font-medium text-primary">{{ $status }}</p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
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

        <div class="grid gap-2">
            <div class="flex items-center">
                <label for="password" class="text-sm font-medium">Password</label>
                @if ($canResetPassword)
                    <a href="{{ route('password.request') }}" class="ml-auto text-sm text-primary underline-offset-4 hover:underline">
                        Forgot your password?
                    </a>
                @endif
            </div>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded border-input">
            Remember me
        </label>

        <button
            type="submit"
            data-test="login-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-primary underline-offset-4 hover:underline">Sign up</a>
    </p>
</x-layouts.guest>
