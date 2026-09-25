<x-layouts.settings title="Security settings">
    <h2 class="mb-1 text-lg font-medium">Password</h2>
    <p class="mb-6 text-sm text-muted-foreground">Update your password</p>

    <form method="POST" action="{{ route('user-password.update') }}" class="grid max-w-lg gap-4">
        @csrf
        @method('PUT')

        <div class="grid gap-2">
            <label for="current_password" class="text-sm font-medium">Current password</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
            @error('current_password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
        </div>

        <button type="submit" class="inline-flex w-fit items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
            Save password
        </button>
    </form>

    @if ($canManageTwoFactor)
        <div class="mt-10 max-w-lg">
            <h2 class="mb-1 text-lg font-medium">Two-factor authentication</h2>
            <p class="text-sm text-muted-foreground">
                {{ $twoFactorEnabled ? 'Two-factor authentication is enabled.' : 'Two-factor authentication is not enabled.' }}
            </p>
        </div>
    @endif

    @if ($canManagePasskeys)
        <div class="mt-10 max-w-lg">
            <h2 class="mb-1 text-lg font-medium">Passkeys</h2>
            @forelse ($passkeys as $passkey)
                <p class="text-sm">{{ $passkey['name'] }}</p>
            @empty
                <p class="text-sm text-muted-foreground">No passkeys registered.</p>
            @endforelse
        </div>
    @endif
</x-layouts.settings>
