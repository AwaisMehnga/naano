<x-layouts.auth
    title="Verify email"
    kicker="Almost in"
    description="Enter the 6-digit code we sent you."
>
    <x-slot:heading>
        Check your
        <x-ui.em>email.</x-ui.em>
    </x-slot:heading>

    @if ($status === 'verification-link-sent')
        <x-ui.alert variant="success" class="mb-6">A new code is on its way.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('verification.code') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.field label="Code" name="code">
            <x-ui.input
                id="code"
                type="text"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                autofocus
                class="text-center text-lg tracking-[0.4em]"
            />
        </x-ui.field>

        <x-ui.button>
            Verify
        </x-ui.button>
    </form>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
        @csrf
        <x-ui.button variant="link">
            Resend code
        </x-ui.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-2">
        @csrf
        <x-ui.button variant="ghost" class="text-muted-foreground">
            Log out
        </x-ui.button>
    </form>

    <x-slot:panel>
        <x-ui.kicker>Next</x-ui.kicker>
        <p class="mt-6 text-4xl font-normal tracking-tight">
            Verify once.
            <x-ui.em>Then finish your profile.</x-ui.em>
        </p>
    </x-slot:panel>
</x-layouts.auth>
