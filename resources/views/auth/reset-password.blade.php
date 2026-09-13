<x-layouts.auth
    title="Reset password"
    kicker="Account"
    description="Choose a new password for this email."
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

        <x-ui.field label="Password" name="password">
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

        <x-ui.button data-test="reset-password-button">
            Reset password
        </x-ui.button>
    </form>

    <x-slot:panel>
        <x-ui.kicker>Almost back</x-ui.kicker>
        <p class="mt-6 text-4xl font-normal tracking-tight">
            Then sign in and open your
            <x-ui.em>workspace.</x-ui.em>
        </p>
    </x-slot:panel>
</x-layouts.auth>
