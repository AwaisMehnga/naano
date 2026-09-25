<x-layouts.auth
    title="Choose a profile"
    kicker="Your account"
    description="Add a creator or company profile. You can add the other later."
>
    <x-slot:heading>
        How do you want to
        <x-ui.em>start?</x-ui.em>
    </x-slot:heading>

    <form method="POST" action="{{ route('profiles.choose.store') }}" class="flex flex-col gap-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex cursor-pointer flex-col gap-2 rounded-lg border border-border bg-background p-5 has-[:checked]:border-primary">
                <input type="radio" name="type" value="creator" class="sr-only" @checked(old('type') === 'creator') required>
                <span class="font-medium text-foreground">Creator</span>
                <span class="text-sm text-muted-foreground">Get booked by B2B brands.</span>
            </label>

            <label class="flex cursor-pointer flex-col gap-2 rounded-lg border border-border bg-background p-5 has-[:checked]:border-primary">
                <input type="radio" name="type" value="company" class="sr-only" @checked(old('type') === 'company')>
                <span class="font-medium text-foreground">Company</span>
                <span class="text-sm text-muted-foreground">Run LinkedIn creator campaigns.</span>
            </label>
        </div>

        @error('type')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror

        <x-ui.button type="submit">Continue</x-ui.button>
    </form>
</x-layouts.auth>
