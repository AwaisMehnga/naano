<x-layouts.auth
    title="Confirm password"
    kicker="Secure"
    description="Confirm your password before continuing."
>
    <x-slot:heading>
        Confirm your
        <x-ui.em>password.</x-ui.em>
    </x-slot:heading>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.field label="Password" name="password">
            <x-ui.input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
            />
        </x-ui.field>

        <x-ui.button data-test="confirm-password-button">
            Confirm password
        </x-ui.button>
    </form>
</x-layouts.auth>
