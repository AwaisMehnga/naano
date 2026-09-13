<x-layouts.auth
    title="Create your account"
    kicker="Get started"
    description="Pick a side. You use that workspace."
    wide
>
    <x-slot:heading>
        Who are you
        <x-ui.em>here as?</x-ui.em>
    </x-slot:heading>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.card href="{{ route('register.creator') }}" class="grid gap-3">
            <x-ui.kicker>Creators</x-ui.kicker>
            <p class="text-2xl font-normal tracking-tight">
                Set a rate.
                <x-ui.em>Get booked.</x-ui.em>
            </p>
            <p class="text-sm text-muted-foreground">Get paid for LinkedIn posts from B2B brands.</p>
            <span class="text-sm text-primary">Continue as creator</span>
        </x-ui.card>

        <x-ui.card href="{{ route('register.company') }}" class="grid gap-3">
            <x-ui.kicker>Companies</x-ui.kicker>
            <p class="text-2xl font-normal tracking-tight">
                Book creators at a
                <x-ui.em>fixed price.</x-ui.em>
            </p>
            <p class="text-sm text-muted-foreground">Find creators and book campaigns from one place.</p>
            <span class="text-sm text-primary">Continue as company</span>
        </x-ui.card>
    </div>

    <p class="mt-10 text-sm text-muted-foreground">
        Already have an account?
        <x-ui.button href="{{ route('login') }}" variant="link">Sign in</x-ui.button>
    </p>

    <x-slot:panel>
        <x-ui.kicker>One marketplace</x-ui.kicker>
        <p class="mt-6 text-4xl font-normal tracking-tight">
            Creators set a price.
            <x-ui.em>Companies book.</x-ui.em>
        </p>
        <p class="mt-6 text-lg leading-relaxed text-muted-foreground">That’s the loop.</p>
    </x-slot:panel>
</x-layouts.auth>
