@php
    $isCreator = $role === 'creator';
@endphp

<x-layouts.guest
    title="Join {{ config('app.name') }}"
    heading="{{ $isCreator ? 'Join as a creator' : 'Join as a company' }}"
    description="{{ $isCreator ? 'Get paid to post for B2B brands you use.' : 'Run LinkedIn creator campaigns that drive pipeline.' }}"
>
    <p class="mb-6 text-sm">
        <a href="{{ route('register') }}" class="text-muted-foreground underline-offset-4 hover:underline">Back</a>
    </p>

    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <label for="first_name" class="text-sm font-medium">First name</label>
                <input
                    id="first_name"
                    type="text"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    required
                    autofocus
                    autocomplete="given-name"
                    class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                >
                @error('first_name')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2">
                <label for="last_name" class="text-sm font-medium">Last name</label>
                <input
                    id="last_name"
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    required
                    autocomplete="family-name"
                    class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                >
                @error('last_name')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">{{ $isCreator ? 'Email' : 'Business email' }}</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                placeholder="{{ $isCreator ? 'you@email.com' : 'you@company.com' }}"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('email')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password" class="text-sm font-medium">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            @error('password')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
        </div>

        <fieldset class="grid gap-2">
            <legend class="text-sm font-medium">How did you hear about us?</legend>
            <div class="grid gap-2">
                @foreach (config('onboarding.hear_about') as $value => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="radio"
                            name="hear_about"
                            value="{{ $value }}"
                            @checked(old('hear_about') === $value)
                            required
                            class="border-input"
                        >
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('hear_about')
                <p class="text-sm text-destructive">{{ $message }}</p>
            @enderror
        </fieldset>

        <button
            type="submit"
            data-test="register-user-button"
            class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
        >
            Continue
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary underline-offset-4 hover:underline">Sign in</a>
    </p>

    <x-slot:panel>
        @if ($isCreator)
            <p class="text-lg font-medium">Your marketplace card</p>
            <p class="mt-2 text-sm text-muted-foreground">Name, rate, and industries. Brands book from that.</p>
        @else
            <p class="text-lg font-medium">Creators. Brands. Results.</p>
            <p class="mt-2 text-sm text-muted-foreground">A brief, a match, a booked post.</p>
        @endif
    </x-slot:panel>
</x-layouts.guest>
