<x-layouts.auth
    title="Sign in"
    kicker="Account"
    description="Campaigns, creators, and payouts in one place."
>
    <x-slot:heading>
        Welcome
        <x-ui.em>back.</x-ui.em>
    </x-slot:heading>

    @if ($status)
        <x-ui.alert variant="success" class="mb-6">{{ $status }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-ui.field label="Email" name="email">
            <x-ui.input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
            />
        </x-ui.field>

        <x-ui.field name="password">
            <div class="flex items-center">
                <label for="password" class="text-sm font-medium">Password</label>
                @if ($canResetPassword)
                    <x-ui.button href="{{ route('password.request') }}" variant="link" class="ml-auto">
                        Forgot password?
                    </x-ui.button>
                @endif
            </div>
            <x-ui.input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
        </x-ui.field>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded-sm border-input">
            Remember me
        </label>

        <x-ui.button data-test="login-button">
            Sign in
        </x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        Don't have an account?
        <x-ui.button href="{{ route('register') }}" variant="link">Sign up</x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker>Workspace</x-ui.kicker>
        <p class="mt-6 text-4xl font-normal tracking-tight">
            Pick up the brief.
            <x-ui.em>Book the post.</x-ui.em>
        </p>
        <p class="mt-6 text-lg leading-relaxed text-muted-foreground">
            Your company or creator workspace is where the work actually finishes.
        </p>
    </x-slot:panel>
</x-layouts.auth>
