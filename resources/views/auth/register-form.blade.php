@php
    $isCreator = $role === 'creator';
@endphp

<x-layouts.auth
    title="Join {{ config('app.name') }}"
    kicker="{{ $isCreator ? 'Creators' : 'Companies' }}"
    description="{{ $isCreator ? 'Get paid to post for B2B brands you use.' : 'Run LinkedIn creator campaigns that drive pipeline.' }}"
>
    <x-slot:heading>
        @if ($isCreator)
            Join as a
            <x-ui.em>creator.</x-ui.em>
        @else
            Join as a
            <x-ui.em>company.</x-ui.em>
        @endif
    </x-slot:heading>

    <p class="mb-8">
        <x-ui.button href="{{ route('register') }}" variant="link">Back</x-ui.button>
    </p>

    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field label="First name" name="first_name">
                <x-ui.input
                    id="first_name"
                    type="text"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    required
                    autofocus
                    autocomplete="given-name"
                />
            </x-ui.field>

            <x-ui.field label="Last name" name="last_name">
                <x-ui.input
                    id="last_name"
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    required
                    autocomplete="family-name"
                />
            </x-ui.field>
        </div>

        <x-ui.field label="{{ $isCreator ? 'Email' : 'Business email' }}" name="email">
            <x-ui.input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', request('email')) }}"
                required
                autocomplete="email"
                placeholder="{{ $isCreator ? 'you@email.com' : 'you@company.com' }}"
            />
        </x-ui.field>

        <x-ui.field label="Password" name="password">
            <x-ui.input
                id="password"
                type="password"
                name="password"
                required
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

        <x-ui.field label="How did you hear about us?" name="hear_about">
            <x-ui.select id="hear_about" name="hear_about" required>
                <option value="">Select</option>
                @foreach (config('onboarding.hear_about') as $value => $label)
                    <option value="{{ $value }}" @selected(old('hear_about') === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.button data-test="register-user-button">
            Create account
        </x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        Already have an account?
        <x-ui.button href="{{ route('login') }}" variant="link">Sign in</x-ui.button>
    </p>

    <x-slot:panel>
        @if ($isCreator)
            <x-ui.kicker>Marketplace card</x-ui.kicker>
            <p class="mt-6 text-4xl font-normal tracking-tight">
                Name, rate, and industries.
                <x-ui.em>Brands book from that.</x-ui.em>
            </p>
        @else
            <x-ui.kicker>Company workspace</x-ui.kicker>
            <p class="mt-6 text-4xl font-normal tracking-tight">
                A brief, a match,
                <x-ui.em>a booked post.</x-ui.em>
            </p>
        @endif
    </x-slot:panel>
</x-layouts.auth>
