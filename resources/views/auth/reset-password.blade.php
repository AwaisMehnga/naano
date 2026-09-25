<x-layouts.auth
    title="Reset password"
    kicker="Account"
    description="Choose a new password, then sign in."
>
    <x-slot:heading>
        Choose a
        <x-ui.em>new password.</x-ui.em>
    </x-slot:heading>

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-ui.field label="Email" name="email">
            <x-ui.input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                readonly
                autocomplete="email"
            />
        </x-ui.field>

        <x-ui.field label="Password" name="password" hint="{{ \App\Support\PasswordPolicy::hint() }}">
            <x-ui.input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
            />
        </x-ui.field>

        <x-ui.field label="Confirm password" name="password_confirmation">
            <x-ui.input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
        </x-ui.field>

        <x-ui.button class="w-full" data-test="reset-password-button">
            Reset password
        </x-ui.button>
    </form>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Almost back</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight">Then open your workspace.</p>
    </x-slot:panel>
</x-layouts.auth>
