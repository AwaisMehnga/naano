<x-layouts.guest
    title="Create your account"
    heading="Who are you here as?"
    description="Pick a side. You can only use that workspace."
>
    <div class="grid gap-3">
        <a
            href="{{ route('register.creator') }}"
            class="rounded-lg border border-border bg-card p-4 transition hover:border-primary"
        >
            <p class="font-medium">Creator</p>
            <p class="mt-1 text-sm text-muted-foreground">Get paid for LinkedIn posts.</p>
        </a>

        <a
            href="{{ route('register.company') }}"
            class="rounded-lg border border-border bg-card p-4 transition hover:border-primary"
        >
            <p class="font-medium">Company</p>
            <p class="mt-1 text-sm text-muted-foreground">Find creators and book campaigns.</p>
        </a>
    </div>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary underline-offset-4 hover:underline">Sign in</a>
    </p>

    <x-slot:panel>
        <p class="text-lg font-medium">One platform. Two sides.</p>
        <p class="mt-2 text-sm text-muted-foreground">Creators set a price. Companies book. That’s the loop.</p>
    </x-slot:panel>
</x-layouts.guest>
