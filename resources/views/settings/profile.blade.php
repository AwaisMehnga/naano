<x-layouts.settings title="Profile settings">
    <h2 class="mb-1 text-lg font-medium">Profile</h2>
    <p class="mb-6 text-sm text-muted-foreground">Update your name and email address</p>

    <form method="POST" action="{{ route('profile.update') }}" class="grid max-w-lg gap-4">
        @csrf
        @method('PATCH')

        <div class="grid gap-2">
            <label for="name" class="text-sm font-medium">Name</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
            @error('name')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
            @error('email')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        @if ($mustVerifyEmail && $user->email_verified_at === null)
            <p class="text-sm text-muted-foreground">
                Your email address is unverified.
                <button form="resend-verification" class="text-foreground underline underline-offset-4">
                    Click here to re-send the verification email.
                </button>
            </p>
            @if (session('status') === 'verification-link-sent')
                <p class="text-sm font-medium text-primary">A new verification link has been sent to your email address.</p>
            @endif
        @endif

        <button type="submit" data-test="update-profile-button" class="inline-flex w-fit items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
            Save
        </button>
    </form>

    @if ($mustVerifyEmail && $user->email_verified_at === null)
        <form id="resend-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
            @csrf
        </form>
    @endif

    <div class="mt-12 max-w-lg space-y-4 rounded-lg border border-destructive/20 p-4">
        <h3 class="font-medium text-destructive">Delete account</h3>
        <p class="text-sm text-muted-foreground">Delete your account and all of its resources. This cannot be undone.</p>
        <form method="POST" action="{{ route('profile.destroy') }}" class="grid gap-3">
            @csrf
            @method('DELETE')
            <input type="password" name="password" required placeholder="Password" autocomplete="current-password" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
            <button type="submit" data-test="delete-user-button" class="inline-flex w-fit items-center justify-center rounded-md bg-destructive px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                Delete account
            </button>
        </form>
    </div>
</x-layouts.settings>
