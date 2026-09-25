<x-layouts.auth
    title="Verify email"
    kicker="Almost in"
    description="Enter the 6-digit code we emailed you."
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
            <div class="rounded-2xl bg-muted p-3">
                <x-ui.input
                    id="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    required
                    autofocus
                    placeholder="000000"
                    class="border-0 bg-transparent text-center text-lg tracking-[0.4em] focus:ring-0"
                />
            </div>
        </x-ui.field>

        <x-ui.button class="w-full">
            Verify
        </x-ui.button>
    </form>

    <div class="mt-6 flex flex-wrap items-center gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button variant="link">Resend code</x-ui.button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button variant="ghost" class="text-muted-foreground">Log out</x-ui.button>
        </form>
    </div>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Then</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight text-balance">
            Choose how you want to start.
        </p>
        <p class="mt-4 text-body leading-relaxed text-primary-foreground/80">
            Creator or company — you can add the other later.
        </p>
    </x-slot:panel>
</x-layouts.auth>
