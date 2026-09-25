<x-layouts.auth
    title="Sign in"
    kicker="Account"
    description="Open your workspace."
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
                placeholder="you@email.com"
            />
        </x-ui.field>

        <x-ui.field name="password">
            <div class="flex items-center gap-3">
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
            <input type="checkbox" name="remember" class="size-4 rounded-sm border-input">
            Remember me
        </label>

        <x-ui.button class="w-full" data-test="login-button">
            Sign in
        </x-ui.button>
    </form>

    <p class="mt-8 text-sm text-muted-foreground">
        New here?
        <x-ui.button href="{{ route('register') }}" variant="link">Create account</x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">Workspace</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight text-balance">
            Campaigns, creators, and payouts — in one place.
        </p>
        <ul class="mt-8 space-y-4 text-body text-primary-foreground/80">
            <li class="flex gap-3"><span class="mt-2 size-2 shrink-0 rounded-full bg-accent"></span>Book LinkedIn creators at a fixed price</li>
            <li class="flex gap-3"><span class="mt-2 size-2 shrink-0 rounded-full bg-accent"></span>Get booked with a clear per-post rate</li>
            <li class="flex gap-3"><span class="mt-2 size-2 shrink-0 rounded-full bg-accent"></span>Briefs and approvals stay in your workspace</li>
        </ul>
    </x-slot:panel>
</x-layouts.auth>
