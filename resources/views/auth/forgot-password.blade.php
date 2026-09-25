<x-layouts.auth
    title="Forgot password"
    kicker="Account"
    description="We’ll email a reset link."
>
    <x-slot:heading>
        Forgot your
        <x-ui.em>password?</x-ui.em>
    </x-slot:heading>

    @if ($status)
        <x-ui.alert variant="success" class="mb-6">{{ $status }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.field label="Email address" name="email">
            <x-ui.input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />
        </x-ui.field>

        <x-ui.button class="w-full" data-test="email-password-reset-link-button">
            Email reset link
        </x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        Remembered it?
        <x-ui.button href="{{ route('login') }}" variant="link">Sign in</x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Reset</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight">One email. A new password.</p>
        <p class="mt-4 text-body text-primary-foreground/80">Check spam if nothing arrives.</p>
    </x-slot:panel>
</x-layouts.auth>
