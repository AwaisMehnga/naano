<x-layouts.auth
    title="Choose a profile"
    kicker="Your account"
    description="Pick one to start. You can add the other later."
    wide
>
    <x-slot:heading>
        How do you want to
        <x-ui.em>start?</x-ui.em>
    </x-slot:heading>

    <form method="POST" action="{{ route('profiles.choose.store') }}" class="flex flex-col gap-6">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="cursor-pointer">
                <x-ui.card selectable class="h-full">
                    <input type="radio" name="type" value="creator" class="sr-only" @checked(old('type') === 'creator') required>
                    <x-ui.kicker>Creators</x-ui.kicker>
                    <p class="mt-4 text-title font-medium tracking-tight">Get booked</p>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                        Verify LinkedIn, set a rate, get booked by B2B brands.
                    </p>
                </x-ui.card>
            </label>

            <label class="cursor-pointer">
                <x-ui.card selectable class="h-full">
                    <input type="radio" name="type" value="company" class="sr-only" @checked(old('type') === 'company')>
                    <x-ui.kicker>Companies</x-ui.kicker>
                    <p class="mt-4 text-title font-medium tracking-tight">Book creators</p>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                        Add your site, draft a brief, run LinkedIn campaigns.
                    </p>
                </x-ui.card>
            </label>
        </div>

        @error('type')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror

        <x-ui.button type="submit" class="w-full sm:w-auto">Continue</x-ui.button>
    </form>

    <x-slot:panel>
        <x-ui.kicker tone="on-primary">The loop</x-ui.kicker>
        <p class="mt-5 text-title font-medium tracking-tight text-balance">
            Creators set a price. Companies book.
        </p>
        <p class="mt-4 text-body leading-relaxed text-primary-foreground/80">
            Same account can hold both profiles when you need them.
        </p>
    </x-slot:panel>
</x-layouts.auth>
