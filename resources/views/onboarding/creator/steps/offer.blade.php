<form data-ajax method="POST" action="{{ route('onboarding.creator.offer') }}" class="flex flex-col gap-5" data-offer-form>
    @csrf

    <div class="grid gap-2">
        <label for="price" class="text-sm font-medium">Net price per post</label>
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">€</span>
            <input
                id="price"
                type="number"
                name="price"
                min="1"
                max="10000"
                step="1"
                required
                value="{{ old('price', $profile->price_cents ? (int) ($profile->price_cents / 100) : 240) }}"
                class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
            >
            <span class="text-sm text-muted-foreground">/ post</span>
        </div>
        @error('price')
            <p class="text-sm text-destructive">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-3" data-bundle-list>
        <p class="text-sm font-medium">Bundle <span class="font-normal text-muted-foreground">(optional)</span></p>
        <template data-bundle-template>
            <div class="grid gap-3 rounded-lg border border-border p-3 sm:grid-cols-2" data-bundle-row>
                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">Posts</label>
                    <input type="number" name="bundles[__INDEX__][posts]" min="2" max="50" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm">
                </div>
                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">Total € (net)</label>
                    <input type="number" name="bundles[__INDEX__][total]" min="1" class="w-full rounded-md border border-input bg-card px-3 py-2 text-sm">
                </div>
            </div>
        </template>
    </div>

    <button type="button" data-add-bundle class="text-sm text-primary underline-offset-4 hover:underline">
        Add a bundle
    </button>

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
        Continue
    </button>
</form>
