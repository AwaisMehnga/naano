<x-layouts.guest
    title="Register"
    heading="Create an account"
    description="Enter your details below to create your account"
>
    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
        @csrf

        <div class="grid gap-2">
            <label for="name" class="text-sm font-medium">Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Full name"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('name')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="email@example.com"
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
            data-test="register-user-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary underline-offset-4 hover:underline">Log in</a>
    </p>
</x-layouts.guest>
