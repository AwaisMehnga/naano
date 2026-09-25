@php
    $recovery = request()->boolean('recovery');
@endphp

<x-layouts.auth
    title="Two-factor authentication"
    kicker="Account"
    description="{{ $recovery ? 'Enter a recovery code.' : 'Enter the code from your authenticator app.' }}"
>
    <x-slot:heading>
        @if ($recovery)
            Recovery
            <x-ui.em>code.</x-ui.em>
        @else
            Authentication
            <x-ui.em>code.</x-ui.em>
        @endif
    </x-slot:heading>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-5">
        @csrf

        @if ($recovery)
            <x-ui.field label="Recovery code" name="recovery_code">
                <x-ui.input
                    type="text"
                    name="recovery_code"
                    required
                    autofocus
                    placeholder="Enter recovery code"
                />
            </x-ui.field>
        @else
            <x-ui.field label="Authenticator code" name="code">
                <div class="rounded-2xl bg-muted p-3">
                    <x-ui.input
                        type="text"
                        name="code"
                        required
                        autofocus
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        class="border-0 bg-transparent text-center tracking-widest focus:ring-0"
                    />
                </div>
            </x-ui.field>
        @endif

        <x-ui.button class="w-full">Continue</x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        Or
        <x-ui.button
            href="{{ route('two-factor.login', $recovery ? [] : ['recovery' => 1]) }}"
            variant="link"
        >
            {{ $recovery ? 'use an authentication code' : 'use a recovery code' }}
        </x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Two-factor</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight">An extra step when password isn’t enough.</p>
    </x-slot:panel>
</x-layouts.auth>
