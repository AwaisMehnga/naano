<x-layouts.auth
    title="Confirm password"
    kicker="Secure"
    description="Confirm your password to continue."
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

        <x-ui.button class="w-full" data-test="confirm-password-button">
            Confirm password
        </x-ui.button>
    </form>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Security</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight">A quick check before sensitive actions.</p>
    </x-slot:panel>
</x-layouts.auth>
