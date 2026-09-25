<x-layouts.auth
    title="Create your account"
    kicker="Get started"
    description="Verify your email next. Then choose creator or company."
>
    <x-slot:heading>
        Join
        <x-ui.em>{{ config('app.name') }}.</x-ui.em>
    </x-slot:heading>

    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf

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

        <x-ui.field label="Email" name="email">
            <x-ui.input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', request('email')) }}"
                required
                autocomplete="email"
                placeholder="you@email.com"
            />
        </x-ui.field>

        <x-ui.field label="Password" name="password" hint="{{ \App\Support\PasswordPolicy::hint() }}">
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

        <x-ui.button class="w-full" data-test="register-user-button">
            Create account
        </x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        Already have an account?
        <x-ui.button href="{{ route('login') }}" variant="link">Sign in</x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Next steps</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight text-balance">
            Account → email code → pick your path.
        </p>
        <ol class="mt-8 space-y-5 text-body text-primary-foreground/80">
            <li class="flex gap-4">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-medium text-accent-foreground">1</span>
                <span>Create your account</span>
            </li>
            <li class="flex gap-4">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-medium text-accent-foreground">2</span>
                <span>Enter the 6-digit email code</span>
            </li>
            <li class="flex gap-4">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-medium text-accent-foreground">3</span>
                <span>Choose creator or company</span>
            </li>
        </ol>
    </x-slot:panel>
</x-layouts.auth>
