<form data-ajax method="POST" action="{{ route('onboarding.creator.offer') }}" class="flex flex-col gap-5" data-offer-form>
    @csrf

    <x-ui.field label="Net price per post" name="price">
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">€</span>
            <x-ui.input
                id="price"
                type="number"
                name="price"
                min="1"
                max="10000"
                step="1"
                required
                value="{{ old('price', $profile->price_cents ? (int) ($profile->price_cents / 100) : 240) }}"
            />
            <span class="text-sm text-muted-foreground">/ post</span>
        </div>
    </x-ui.field>

    <div class="grid gap-3" data-bundle-list>
        <p class="text-sm font-medium">Bundle <span class="font-normal text-muted-foreground">(optional)</span></p>
        <template data-bundle-template>
            <div class="grid gap-3 rounded-lg border border-border p-3 sm:grid-cols-2" data-bundle-row>
                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">Posts</label>
                    <x-ui.input type="number" name="bundles[__INDEX__][posts]" min="2" max="50" />
                </div>
                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">Total € (net)</label>
                    <x-ui.input type="number" name="bundles[__INDEX__][total]" min="1" />
                </div>
            </div>
        </template>
    </div>

    <x-ui.button type="button" variant="link" data-add-bundle>
        Add a bundle
    </x-ui.button>

    <x-ui.button>
        Continue
    </x-ui.button>
</form>
