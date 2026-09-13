@php
    $recovery = request()->boolean('recovery');
@endphp

<x-layouts.auth
    title="Two-factor authentication"
    kicker="Account"
    description="{{ $recovery
        ? 'Enter one of your emergency recovery codes.'
        : 'Enter the code from your authenticator app.' }}"
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
            <x-ui.field name="recovery_code">
                <x-ui.input
                    type="text"
                    name="recovery_code"
                    required
                    autofocus
                    placeholder="Enter recovery code"
                />
            </x-ui.field>
        @else
            <x-ui.field name="code">
                <x-ui.input
                    type="text"
                    name="code"
                    required
                    autofocus
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="Authentication code"
                    class="text-center tracking-widest"
                />
            </x-ui.field>
        @endif

        <x-ui.button>
            Continue
        </x-ui.button>
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
</x-layouts.auth>
